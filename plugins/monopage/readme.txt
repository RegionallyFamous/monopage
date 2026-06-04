=== Monopage ===
Contributors: weirdpress
Tags: site editor, one page, block theme, wp-cli
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.2.38
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monopage focuses WordPress around the Site Editor and one editable homepage template.

== Description ==

Monopage keeps WordPress intact while narrowing the authoring workflow for one-page sites.

V1 features:

* Setup helper that creates or reuses a Home page and configures it as the static front page.
* Launch-ready one-page marketing homepage saved as the editable front-page template when Monopage Canvas is active.
* Focus Mode that hides distracting admin menus and redirects generic admin entrypoints to the Site Editor.
* Site Editor focus defaults that open the canvas directly and keep the top toolbar enabled.
* Bundled Monopage Canvas section patterns for offers, proof, pricing, FAQs, and closing CTAs.
* Admin escape hatch for full WordPress dashboard access.
* WP-CLI commands for status, setup, and Focus Mode toggles.
* Designed to pair with the Monopage Canvas block theme.

== WP-CLI ==

wp monopage status
wp monopage validate
wp monopage validate --require-focus
wp monopage validate --check-http
wp monopage setup
wp monopage setup --force-home
wp monopage setup --force-template
wp monopage setup --activate-theme
wp monopage focus enable
wp monopage focus disable

== Changelog ==

= 0.2.38 =
* Show Codex skill state in status.

= 0.2.37 =
* Add fast preflight workflow.

= 0.2.36 =
* Add project status dashboard.

= 0.2.35 =
* Add changed-file check recommendations.

= 0.2.34 =
* Add local visual review workflow.

= 0.2.33 =
* Add local homepage screenshot capture.

= 0.2.32 =
* Refine Canvas hero composition.

= 0.2.31 =
* Validate package script references.

= 0.2.30 =
* Validate documented helper commands.

= 0.2.29 =
* Add repository and source archive hygiene checks.

= 0.2.28 =
* Auto-discover JavaScript syntax checks.

= 0.2.27 =
* Add Docker-free CI workflow and package artifact gate.

= 0.2.26 =
* Route release local checks through local readiness workflow.

= 0.2.25 =
* Add one-command local readiness workflow.

= 0.2.24 =
* Add authenticated Focus Mode admin smoke test.

= 0.2.23 =
* Add deploy dry-run safety validation.

= 0.2.22 =
* Refresh default Canvas with future-agency Riso art and sharper starter copy.

= 0.2.21 =
* Verify package contents during direct deploys.

= 0.2.20 =
* Add package content verification for built ZIPs.

= 0.2.19 =
* Add Codex skill contract validation.

= 0.2.18 =
* Add Playground Blueprint and seamless URL validation.

= 0.2.17 =
* Add a rendered homepage smoke check to the release gate.

= 0.2.16 =
* Refresh the local saved Canvas template during release checks.
* Sharpen the default Canvas theme with refreshed Riso assets and campaign-style starter copy.

= 0.2.15 =
* Add a one-command release verification gate.

= 0.2.14 =
* Validate bundled Canvas pattern links at runtime and make Plugin Check CLI loading automatic.

= 0.2.13 =
* Add WP-CLI validation and deploy setup health checks.

= 0.2.12 =
* Add bundled Canvas section patterns and pattern link validation.

= 0.2.11 =
* Refine Canvas design, block-first styling, validation, and Plugin Check workflow.

= 0.2.10 =
* Load Canvas styles explicitly on the public front end.

= 0.2.9 =
* Add a fresh ImageGen hero asset for the preinstalled Canvas theme.

= 0.2.8 =
* Add a version setter and package-time version guard.

= 0.2.7 =
* Add release metadata checks and a doctor command for local setup visibility.

= 0.2.6 =
* Add an opt-in template refresh path for applying the current Monopage Canvas default to the saved front-page template.

= 0.2.5 =
* Add a Codex skill install helper for the Monopage deployment skill.

= 0.2.4 =
* Rename WPOP to Monopage, expand the default Canvas page, and prevent the Site Editor navigation toggle in Focus Mode.

= 0.2.3 =
* Redirect routing Home page edits to the Site Editor template and explain the Home/template split.

= 0.2.2 =
* Keep starter-template navigation strictly on-page and add a template-link check.

= 0.2.1 =
* Load Canvas styles in the Site Editor and clean template serialization warnings.

= 0.2.0 =
* Add a polished default one-page marketing homepage and seed it into the Site Editor on setup.

= 0.1.1 =
* Make Site Editor top-toolbar enforcement more robust and refresh admin asset caching.

= 0.1.0 =
* Initial standalone Monopage plugin.
