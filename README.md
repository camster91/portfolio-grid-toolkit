# Portfolio Grid Toolkit

A brand-neutral WordPress plugin for managing portfolio projects and showing them as a responsive, filterable video grid with an accessible playback dialog.

## What it does

Portfolio Grid Toolkit gives editors a `portfolio` content type with structured credits and a video URL, then renders those projects anywhere through a single shortcode. Clicking a project opens a native `<dialog>` that plays the YouTube, Vimeo or direct video file. The plugin only adds content and a shortcode: it does not change the site's homepage, navigation, contact page, SEO metadata or theme layout, and it ships no brand assets, fonts, client media or contact details.

## Features

- **Portfolio post type** with title, featured image, revisions and page attributes. If another plugin or theme already registers `portfolio`, that registration is kept and this plugin only attaches its fields and taxonomies.
- **Collection and Artist taxonomies** (hierarchical collections, flat artists), plus post tags for front-end filtering.
- **Project Details meta box**: grid subtitle, main video URL, and client, agency, director, editor and additional production credits. All fields are registered with `register_post_meta`, exposed to the REST API and sanitized on save.
- **`[pgtk_work]` shortcode** that renders a 3-column grid (2 columns under 1024px, 1 column under 768px) with optional tag filter buttons.
- **Accessible video dialog**: native `<dialog>` with focus return, Escape to close, a focus-trap fallback for browsers without `showModal()`, and credits rendered with `textContent` so meta values cannot inject markup.
- **Drag-and-drop Sort Order screen** (jQuery UI Sortable) scoped by status, Collection and Artist. Saves over AJAX and rejects stale or partial orderings.
- **Admin Overview page** with published and draft counts, quick links and a project checklist.
- **Scoped asset loading**: the grid CSS and JS load only on singular pages whose content (or Elementor data) contains `[pgtk_work`.
- **Draft previews**: on an authorized WordPress preview, editors also see projects they can edit. Public visitors only see published projects.
- **Safe uninstall**: projects, terms and credits are site content and are left in place when the plugin is deleted.

## Requirements

- WordPress with the block editor or classic editor. The plugin header does not declare a minimum WordPress or PHP version; CI lints and tests on PHP 8.2.
- Optional: Elementor. The shortcode works inside a Shortcode widget and shows a placeholder in the Elementor editor.

## Installation

1. Build a release zip:

   ```bash
   bash scripts/build-plugin.sh
   ```

   This writes `dist/portfolio-grid-toolkit-<version>.zip` containing only the plugin files. You can also zip the `portfolio-grid-toolkit` directory yourself.
2. In WordPress, go to **Plugins > Add New > Upload Plugin**, upload the zip and activate it.
3. Add projects under **Portfolios**. Set a featured image and fill in **Project Details** (at least the video URL).
4. Optionally assign a Collection, Artist and tags, then publish.
5. Use **Portfolios > Sort Order** to set the display order.
6. Add `[pgtk_work]` to a page (or an Elementor Shortcode widget).

Do not activate this alongside another plugin that owns the same `portfolio` content model or the `pgtk_` function prefix. It is a separate plugin, not a drop-in replacement for one that uses different shortcodes or function names.

## Usage

`[pgtk_work]` shows all published projects in display order (highest menu order first).

| Attribute | Example | Effect |
|---|---|---|
| `posts_per_page` | `posts_per_page="6"` | Limit the number of projects (1 to 100). Omit for all. |
| `collection` | `collection="campaigns"` | Only projects in this Collection (slug). |
| `artist` | `artist="creator-slug"` | Only projects for this Artist (slug). |
| `category` | `category="featured"` | Only projects with this post tag (slug). |
| `heading` | `heading="Selected work"` | Adds a visible `h1` above the grid. |
| `variant` | `variant="artist"` | Shows the agency credit (falling back to the subtitle) under each title. |
| `filters` | `filters="show"` | Adds tag filter buttons above the grid. |

Taxonomy filters combine with AND. Examples:

```text
[pgtk_work collection="campaigns" filters="show"]
[pgtk_work artist="creator-slug" heading="Selected work" variant="artist" posts_per_page="6"]
```

A project without a video URL still appears in the grid, but its button is disabled. If a project has no featured image, the grid falls back to a legacy `image` attachment ID meta field when present.

### Permissions

| Screen or action | Capability |
|---|---|
| Overview page | `edit_posts` |
| Sort Order page and saving order | `edit_others_posts`, plus `edit_post` on every reordered project, with a nonce |
| Saving Project Details | `edit_post` on the project, with a nonce |

## Project structure

```text
portfolio-grid-toolkit.php   Plugin header, constants, asset registration
includes/
  post-type.php              Post type, taxonomies, post meta, meta box, list columns
  shortcode.php              [pgtk_work] renderer and credit helpers
  sort.php                   Sort Order admin page and AJAX handler
  admin.php                  Overview page and plugin action link
assets/
  css/style.css, js/main.js  Front-end grid, filters and video dialog
  css/sort.css, js/sort.js   Sort Order screen
tests/                       Standalone PHP contract tests
scripts/build-plugin.sh      Builds the release zip into dist/
uninstall.php                Intentionally keeps all content
```

## Development and tests

The tests are plain PHP scripts with lightweight WordPress function stubs, so they run without a WordPress install:

```bash
php tests/post-type-test.php
php tests/sort-test.php
php tests/shortcode-test.php
php tests/assets-test.php
```

GitHub Actions (`.github/workflows/ci.yml`) runs these on every push and pull request, along with PHP syntax linting, `node --check` on the JavaScript, the release build, and a check that the zip contains no raw video or proof files.

## License

MIT. Copyright (c) 2026 Cameron Ashley. See [LICENSE](LICENSE).
