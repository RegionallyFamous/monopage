=== Monopage ===
Contributors: regionallyfamous
Tags: site editor, one page, block theme, wp-cli
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-native one-page WordPress with the Site Editor, blocks, real hosting, Codex deployment support, and no admin sprawl.

== Description ==

Monopage turns WordPress into a focused one-page site studio.

It keeps the best WordPress pieces: the Site Editor, blocks, media, block themes, users, WP-CLI, and real hosting. Then it removes the things most one-page sites do not need: page mazes, blog/archive assumptions, off-page menus, dashboard panels, and settings sprawl.

Use AI to write, revise, design, and deploy. Monopage keeps the site model simple enough for an assistant to work cleanly: one editable homepage canvas, same-page anchor navigation, and a calm authoring surface.

Monopage includes:

* One-page setup that creates or reuses a Home page and makes it the static front page.
* Monopage Canvas, a block theme with a launch-ready starter homepage.
* Focus Mode that opens WordPress directly to the one-page Site Editor canvas.
* Same-page navigation rules for menus, footer links, and starter calls to action.
* Codex skill support for packaging, deploying, and validating Monopage sites.
* WP-CLI commands for setup, validation, status, and Focus Mode.

== Installation ==

1. Install and activate the Monopage Canvas theme.
2. Install and activate the Monopage plugin.
3. Run Monopage setup with WP-CLI or through the companion Codex skill.
4. Open the Site Editor and edit the homepage canvas.

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

== Frequently Asked Questions ==

= Is Monopage a separate website builder? =

No. Monopage is WordPress, focused around one editable homepage template.

= Can I use normal WordPress hosting? =

Yes. Monopage deploys to real WordPress hosts and keeps WordPress core intact.

= Does Monopage remove WordPress capabilities? =

No. Focus Mode is an authoring experience, not a security boundary. WordPress capabilities still control access.

== Changelog ==

= 1.0.0 =
* Initial public release of Monopage: one-page setup, Monopage Canvas, Focus Mode, same-page navigation rules, WP-CLI commands, Codex deployment support, Playground demo, and release checks.
