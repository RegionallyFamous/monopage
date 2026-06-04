=== WPOP ===
Contributors: weirdpress
Tags: site editor, one page, block theme, wp-cli
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress One Pager focuses WordPress around the Site Editor and one editable homepage template.

== Description ==

WPOP keeps WordPress intact while narrowing the authoring workflow for one-page sites.

V1 features:

* Setup helper that creates or reuses a Home page and configures it as the static front page.
* Launch-ready one-page marketing homepage saved as the editable front-page template when WPOP Canvas is active.
* Focus Mode that hides distracting admin menus and redirects generic admin entrypoints to the Site Editor.
* Site Editor focus defaults that open the canvas directly and keep the top toolbar enabled.
* Admin escape hatch for full WordPress dashboard access.
* WP-CLI commands for status, setup, and Focus Mode toggles.
* Designed to pair with the WPOP Canvas block theme.

== WP-CLI ==

wp wpop status
wp wpop setup
wp wpop setup --force-home
wp wpop setup --activate-theme
wp wpop focus enable
wp wpop focus disable

== Changelog ==

= 0.2.0 =
* Add a polished default one-page marketing homepage and seed it into the Site Editor on setup.

= 0.1.1 =
* Make Site Editor top-toolbar enforcement more robust and refresh admin asset caching.

= 0.1.0 =
* Initial standalone WPOP plugin.
