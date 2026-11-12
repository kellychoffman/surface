=== Surface ===
Contributors: kellychoffman
Tags: admin, dashboard, minimal, ai, workflow
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replaces the admin with one quiet list of things to do. Do them yourself, or let Surface do them for you.

== Description ==

Surface is a proof of concept. After you log in, there is no dashboard, menu or admin bar. You see a short list: reply to a comment, update plugins, finish a draft, write a post. Each item can be done by you (the needed UI appears inline) or by Surface.

Optional: add an Anthropic API key and replies and posts are drafted by Claude. Comment text is sent to the Anthropic API only when a key is set.

== Installation ==

1. Upload the `surface` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Log in again.

Use `/wp-admin/index.php?classic=1` or the Classic admin link to reach the normal dashboard.

== Changelog ==

= 0.1.0 =
* First proof of concept.
