# Portfolio Grid Toolkit

A WordPress plugin for managing portfolio projects and displaying responsive video grids. It does not change the site's homepage, navigation, contact page, SEO metadata, or theme layout.

## Features

- A `portfolio` post type with optional Collection and Artist taxonomies. If another component already registers `portfolio`, this plugin leaves that registration in place.
- Project title, featured image, subtitle, video URL, and structured credits.
- A filterable grid with an accessible project-video dialog. YouTube, Vimeo, and direct video URLs are supported.
- Draft records on authorized preview pages; public pages show published records only.
- An admin overview and drag-and-drop display ordering with capability and nonce checks.
- No bundled brand assets, fonts, client media, contact details, or site-specific page rules.

## Installation

1. Build with `bash scripts/build-plugin.sh`, or zip the `portfolio-grid-toolkit` directory.
2. Upload the archive through WordPress **Plugins → Add New → Upload Plugin** and activate it.
3. Add projects under **Portfolios** and set a featured image and video URL.
4. Insert `[pgtk_work]` on a page. Elementor users can use a Shortcode widget.

Do not activate this alongside another plugin that owns the same `portfolio` content model or the `pgtk_` API. This is a separate plugin, not a drop-in upgrade for an installation using different shortcodes or function names. Existing posts are not deleted on uninstall.

## Shortcode

`[pgtk_work]` displays all published projects in descending display order. Optional attributes:

- `posts_per_page="6"` limits the grid (maximum 100; omit for all projects).
- `collection="campaigns"` and `artist="creator-slug"` filter by taxonomy slug.
- `category="featured"` filters by post-tag slug.
- `heading="Selected work"` adds a visible heading.
- `variant="artist"` shows an agency/subtitle line when one is supplied.
- `filters="show"` adds interactive post-tag filters.

Only published projects appear for public visitors. On an authorized WordPress preview, editors may also see drafts that they can edit. A project without a video is shown but its dialog button is disabled.

## Development checks

Run `php tests/post-type-test.php`, `php tests/sort-test.php`, `php tests/shortcode-test.php`, `php tests/assets-test.php`, PHP lint, JavaScript syntax checks, and `bash scripts/build-plugin.sh`. Inspect the archive contents before installing it.

## License

MIT. Copyright © 2026 Cameron Ashley; see `LICENSE`.
