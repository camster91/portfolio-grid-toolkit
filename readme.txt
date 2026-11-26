=== Ashbi Portfolio Grid ===
Contributors: camster91
Tags: portfolio, video portfolio, filterable grid, shortcode, gallery
Requires at least: 6.6
Tested up to: 7.2
Requires PHP: 8.2
Stable tag: 1.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your video and design work in a filterable grid that fits any theme. Design it visually, then paste one shortcode.

== Description ==

Ashbi Portfolio Grid turns your projects into a clean, filterable grid. Click a project and its video plays in a pop-up with the credits. It uses your theme's fonts and colors, so it looks like it belongs on your site.

**Simple to start**

* Add a project: title, image, video link and credits.
* Open **Grid Studio**, pick a layout and a look, and watch the preview update.
* Copy one shortcode and paste it into any page, block or Elementor widget.

**Powerful when you need it**

* Three layouts: grid, bento (big and small tiles) and list.
* Four presets: Minimal, Editorial, Bold and Glass. One accent color.
* Light, dark or automatic colors.
* Filter buttons from your tags, plus Collections and Artists to group work.
* Drag and drop to set the order.
* Plays YouTube, Vimeo and video files.

**Safe and private**

* No tracking, no ads, no account and no outside services.
* Works with keyboards and screen readers. The pop-up returns focus when it closes.
* The styles and scripts only load on pages that show the grid.
* Deleting the plugin keeps your projects. They're your content.

Part of the free Ashbi plugin family, with [Ashbi Mega Menu](https://wordpress.org/plugins/ashbi-mega-menu/). All Ashbi plugins share the same look and builder.

== Installation ==

1. Install and activate the plugin.
2. Go to **Portfolios → Add Project** and add a few projects.
3. Go to **Portfolios → Grid Studio**, design your grid and press **Copy shortcode**.
4. Paste it into a Shortcode block, an Elementor Shortcode widget, or any text area that runs shortcodes.

== Frequently Asked Questions ==

= Which shortcode do I use? =

`[ashbi_portfolio]`. Grid Studio writes it for you. Examples:

* `[ashbi_portfolio layout="bento" preset="glass" accent="#e4572e"]`
* `[ashbi_portfolio collection="campaigns" filters="show" limit="6"]`
* `[ashbi_portfolio artist="creator-slug" variant="artist" heading="Selected work"]`

= What can I set? =

`layout` (grid, bento, list), `preset` (minimal, editorial, bold, glass), `theme` (light, dark, auto), `accent` (a hex color), `columns` (1–6), `limit`, `collection`, `artist`, `category` (a tag), `filters` (show, hide), `heading`, `variant` (work, artist) and `class`.

= I used Portfolio Grid Toolkit. Do my pages still work? =

Yes. The old `[pgtk_work]` shortcode still works, and your projects, credits and order stay the same.

= My theme already has a "portfolio" post type. =

The plugin keeps your theme's post type and adds its fields and grid to it.

= Does it work with Elementor? =

Yes. Use a Shortcode widget. In the Elementor editor you see a placeholder. The grid shows in the preview and on the live page.

== Screenshots ==

1. Grid Studio: pick a layout, preset and accent, and see your projects update live.
2. Bento layout on a dark theme.
3. The project pop-up with video and credits.
4. Drag-and-drop ordering.

== Changelog ==

= 1.1.0 =
* New name: Ashbi Portfolio Grid.
* New Grid Studio with a live preview and a shortcode that writes itself.
* New bento and list layouts, four presets, an accent color, and light, dark or auto colors.
* New `[ashbi_portfolio]` shortcode. `[pgtk_work]` still works.
* Now licensed GPL-2.0-or-later.

= 1.0.0 =
* First release as Portfolio Grid Toolkit.

== Upgrade Notice ==

= 1.1.0 =
New name, a visual Grid Studio and new layouts. Your projects and existing shortcodes keep working.
