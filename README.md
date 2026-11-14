# Surface

A proof of concept: what if logging in to WordPress didn't show you an admin at all?

Surface removes the dashboard, the menu and the admin bar from your first screen. You log in and see one short list of things that need you:

- a comment waiting for a reply
- plugins that need updating
- a WordPress core update
- a draft that has been sitting too long
- a post you haven't written

Each item has two buttons. **Do it myself** opens only the UI that task needs, right there in the row: a reply box, a post editor, a list of plugins to update one by one. You never get sent to wp-admin. Inside a composer, **Suggest a draft** fills the box for you to edit. **Do it for me** just does it, then shows what it did with an **Undo** where that makes sense.

There is no navigation to learn. The interface is composed per task and disappears when the task is done.

## How it works

| Piece | What it does |
| --- | --- |
| `Surface_Actions` | Looks at the site (pending comments, plugin updates, drafts, post cadence) and returns a ranked list of actions, then runs or undoes them. |
| `Surface_Rest` | A small REST API under `surface/v1` that the screen talks to. Every route requires a logged-in user, and each action checks its own capability. |
| `Surface_Page` | Redirects login and the dashboard to a bare full-screen page with no WordPress chrome. |
| `Surface_AI` | Optional. If you add an Anthropic API key, replies and posts are written by Claude. Without one, Surface falls back to plain templates. |

The front end is vanilla JS and CSS. There is no build step.

## Demo mode (default)

Out of the box Surface runs as a prototype: fake tasks, fake "AI" replies and drafts, no API key, and nothing is written to your site. Buttons really work and show realistic results, so you can click through the whole idea on an empty install. Switch to your real site data with the **Demo mode** link in the footer, or force it in `wp-config.php` with `define( 'SURFACE_DEMO', false );`.

## Install

1. Download this repo as a zip, or clone it into `wp-content/plugins/surface`.
2. Activate **Surface**.
3. Log in again. You land on Surface.

Surface never links to wp-admin. If you need the old dashboard, `/wp-admin/index.php?classic=1` skips the redirect, and deactivating the plugin restores everything.

## Optional: Claude drafting

Either click **Let Claude draft for you** on the Surface screen and paste a key, or define it in `wp-config.php`:

```php
define( 'SURFACE_ANTHROPIC_KEY', 'sk-ant-...' );
```

Comment text is sent to the Anthropic API when drafting a reply. Nothing is sent unless a key is set. The model can be changed with the `surface_model` filter.

## Safety

- "Do it for me" on a post only ever saves a **draft**.
- Replies and drafts made by Surface are tagged, so **Undo** can only remove things Surface itself created.
- Core updates are never done for you automatically. You review and confirm them inline.
- Plugin and core updates need WordPress to be able to write to disk.

## Status

Proof of concept, v0.1. Not tested on multisite. It only looks at what the current user can do, so an editor and an administrator see different lists.

## Ideas for next

- More action sources: failed Site Health checks, scheduled posts, form entries, WooCommerce orders
- Learn which actions you always hand off, and offer to do those automatically
- Compose richer UI on demand (media picker, SEO check) instead of linking out

## License

GPL-2.0-or-later
