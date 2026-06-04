(function (wp, document, window) {
	"use strict";

	if (!wp || !wp.data || !wp.domReady || !document || !window) {
		return;
	}

	var disabledNavigationToggles = new WeakSet();
	var updateScheduled = false;
	var editorPreferenceScopes = ["core", "core/edit-site", "core/edit-post"];
	var disabledEditorModes = ["distractionFree", "focusMode", "spotlightMode"];
	var navigationToggleSelectors = [
		".edit-site-editor__view-mode-toggle",
		".edit-site-layout__view-mode-toggle",
		".edit-site-layout__view-mode-toggle-button",
		".edit-site-header__view-mode-toggle",
		".interface-interface-skeleton__header button[aria-label='Open Navigation']",
		".interface-interface-skeleton__header button[aria-label='Open navigation']",
		".interface-interface-skeleton__header a[aria-label='Open Navigation']",
		".interface-interface-skeleton__header a[aria-label='Open navigation']",
	];

	function setPreference(scope, key, value) {
		var preferences = wp.data.select("core/preferences");
		var dispatch = wp.data.dispatch("core/preferences");

		if (!preferences || !dispatch || !dispatch.set) {
			return false;
		}

		if (preferences.get(scope, key) !== value) {
			dispatch.set(scope, key, value);
		}

		return true;
	}

	function setEditorPreference(key, value) {
		var changed = false;

		editorPreferenceScopes.forEach(function (scope) {
			changed = setPreference(scope, key, value) || changed;
		});

		return changed;
	}

	function forceEditorPreferences() {
		setEditorPreference("fixedToolbar", true);
		disabledEditorModes.forEach(function (mode) {
			setEditorPreference(mode, false);
		});

		["core/edit-site", "core/edit-post"].forEach(function (storeName) {
			var store = wp.data.select(storeName);
			var dispatch = wp.data.dispatch(storeName);

			setFeatureActive(store, dispatch, "fixedToolbar", true);

			disabledEditorModes.forEach(function (mode) {
				setFeatureActive(store, dispatch, mode, false);
			});
		});
	}

	function setFeatureActive(store, dispatch, feature, active) {
		var current;

		if (
			!store ||
			!dispatch ||
			!store.isFeatureActive ||
			!dispatch.toggleFeature
		) {
			return;
		}

		current = store.isFeatureActive(feature);

		if ("boolean" !== typeof current || current === active) {
			return;
		}

		dispatch.toggleFeature(feature);
	}

	function blockNavigationToggle(event) {
		event.preventDefault();
		event.stopImmediatePropagation();
	}

	function blockNavigationToggleKeys(event) {
		if ("Enter" === event.key || " " === event.key) {
			blockNavigationToggle(event);
		}
	}

	function disableNavigationToggle(element) {
		if (!element || disabledNavigationToggles.has(element)) {
			return;
		}

		disabledNavigationToggles.add(element);
		element.classList.add("monopage-site-editor-nav-toggle-disabled");
		element.setAttribute("aria-hidden", "true");
		element.setAttribute("tabindex", "-1");

		if ("BUTTON" === element.tagName) {
			element.disabled = true;
		}

		if ("A" === element.tagName) {
			element.removeAttribute("href");
			element.setAttribute("role", "presentation");
		}

		element.addEventListener("click", blockNavigationToggle, true);
		element.addEventListener("keydown", blockNavigationToggleKeys, true);
	}

	function disableSiteEditorNavigationToggle() {
		navigationToggleSelectors.forEach(function (selector) {
			document.querySelectorAll(selector).forEach(disableNavigationToggle);
		});
	}

	function refreshEditorChrome() {
		forceEditorPreferences();
		disableSiteEditorNavigationToggle();
	}

	function scheduleEditorChromeRefresh() {
		if (updateScheduled) {
			return;
		}

		updateScheduled = true;
		window.requestAnimationFrame(function () {
			updateScheduled = false;
			refreshEditorChrome();
		});
	}

	wp.domReady(function () {
		refreshEditorChrome();

		wp.data.subscribe(scheduleEditorChromeRefresh);

		if (window.MutationObserver && document.body) {
			new window.MutationObserver(scheduleEditorChromeRefresh).observe(document.body, {
				childList: true,
				subtree: true,
			});
		}
	});
})(window.wp, window.document, window);
