=== Dynamo ===

Theme Name: Dynamo
Tags: custom-colors, custom-menu, custom-logo, featured-images, full-width-template, sticky-post, theme-options, threaded-comments, translation-ready, block-styles, wide-blocks
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A performant, token-driven WordPress theme with dynamic CSS custom properties.

== Description ==

Dynamo is a lightweight, extensible WordPress theme where every visual design
token — colours, typography, spacing, layout, borders, and shadows — is
controlled through the Customizer and reflected instantly across both the front
end and the block editor.

Key features:

* Dynamic CSS custom properties (`--dynamo-*`) generated from Customizer settings
* Live Customizer preview with no page reload required
* theme.json integration for seamless block editor colour and typography sync
* Transient-based CSS caching for zero-overhead front-end delivery
* React-powered admin options page with Layout, Features, and Performance tabs
* Filter-per-module architecture for plugin and child-theme extensibility
* Six CSS modules: Colors, Typography, Spacing, Layout, Borders, Shadows
* Public filter API locked and documented as stable from v1.0.0

WooCommerce integration (requires WooCommerce plugin):

* Full WooCommerce theme support with Dynamo-compatible wrappers
* Token-driven WooCommerce styling: colours and layout driven by Customizer tokens
* Shop grid: Customizer controls for column count and products per page
* Single product page: show/hide toggles for title, price, rating, excerpt,
  add-to-cart button, meta, and related products column count
* Header cart icon with position control and AJAX-refreshable item count
* Quantity +/− buttons with WooCommerce cart event integration
* Cart and checkout: checkout button label override and cross-sells toggle
* Product card element toggles: image, title, rating, price, short description,
  and add-to-cart independently shown or hidden on the shop loop

Font system:

* Slug-based font manifest for registering custom font families
* @font-face emission from the manifest at render time
* Graceful degradation when font slugs cannot be resolved

Customizer binding system:

* Structured binding types: text, textarea, number/range, radio/select, URL,
  image, media, date, and code — all wired through a unified binding layer
* Property-prerequisite validation: controls declare a `requires` field and are
  shown or hidden based on other token values
* `dynamo-extend-customizer.php` developer template for adding custom controls
  without editing theme files

== Installation ==

1. In your WordPress admin, go to Appearance → Themes → Add New.
2. Click Upload Theme and select the dynamo.zip file.
3. Click Install Now, then Activate.
4. Go to Appearance → Customizer to adjust colours, typography, and spacing.
5. Go to Appearance → Dynamo Settings for layout, feature, and performance options.

== Frequently Asked Questions ==

= Can I use this theme with a page builder? =

Dynamo is designed for the WordPress block editor (Gutenberg). Compatibility
with third-party page builders is not guaranteed.

= How do I extend the design tokens from a child theme or plugin? =

Use the `dynamo_token_defaults` filter to add or override token values, and the
`dynamo_css_{module}` filters to modify per-module CSS output. See the inline
docblocks on each filter for full parameter documentation.

= Does Dynamo support right-to-left (RTL) languages? =

RTL stylesheet support is planned for a future release.

== Changelog ==

= 1.0.0 =
* Initial release.
* Six CSS modules: Colors, Typography, Spacing, Layout, Borders, Shadows.
* Customizer integration with live preview.
* theme.json sync for block editor.
* React admin options page (Layout, Features, Performance tabs).
* Stable public filter API: `dynamo_token_defaults`, `dynamo_css_modules`, `dynamo_css_{module}`.
* Public helper: `dynamo_bust_css_cache()`.

== Copyright ==

Dynamo WordPress Theme, Copyright 2024 Sagiris Web Development.
Dynamo is distributed under the terms of the GNU GPL v2 or later.
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Resources ==

Dynamo WordPress Theme, Copyright 2024 Sagiris Web Development
License: GPL-2.0-or-later
Source: https://sagirisdev.com/

All JavaScript files in assets/js/ are original work by Sagiris Web Development
and are distributed under the same GPL-2.0-or-later license as the theme.

The options panel (assets/js/options/options.js) is compiled from source
(src/admin/options.js) using @wordpress/scripts. It declares wp-element,
wp-components, wp-api-fetch, and react-jsx-runtime as external dependencies
provided by WordPress core — no third-party code is bundled in the output file.

Screenshot: [to be added]
