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

Dynamo WordPress Theme, Copyright 2024.
Dynamo is distributed under the terms of the GNU GPL v2 or later.
License URI: https://www.gnu.org/licenses/gpl-2.0.html
