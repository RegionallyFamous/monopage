(function (wp) {
	"use strict";

	if (!wp || !wp.data || !wp.domReady) {
		return;
	}

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

	function forceTopToolbar() {
		if (setPreference("core", "fixedToolbar", true)) {
			setPreference("core", "distractionFree", false);
			return;
		}

		["core/edit-site", "core/edit-post"].forEach(function (storeName) {
			var store = wp.data.select(storeName);
			var dispatch = wp.data.dispatch(storeName);

			if (
				store &&
				dispatch &&
				store.isFeatureActive &&
				dispatch.toggleFeature &&
				!store.isFeatureActive("fixedToolbar")
			) {
				dispatch.toggleFeature("fixedToolbar");
			}

			if (
				store &&
				dispatch &&
				store.isFeatureActive &&
				dispatch.toggleFeature &&
				store.isFeatureActive("distractionFree")
			) {
				dispatch.toggleFeature("distractionFree");
			}
		});
	}

	wp.domReady(function () {
		forceTopToolbar();

		wp.data.subscribe(function () {
			forceTopToolbar();
		});
	});
})(window.wp);
