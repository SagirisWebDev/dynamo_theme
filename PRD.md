# PRD: Dynamo WordPress Theme

Status: needs-triage

## Problem Statement

WordPress site builders need a performant, extensible theme where every visual design token — colours, typography, spacing, layout, borders, and shadows — is controlled through the Customizer and reflected instantly across both the front end and the block editor. Existing themes either hardcode styles (requiring developer edits for every brand change) or ship bloated option frameworks that are hard to extend. There is no lightweight, hook-driven theme built from first principles for WordPress 6.0+ that treats CSS custom properties as the single source of truth.

## Solution

Dynamo is a hybrid WordPress theme (classic PHP templates + `theme.json`) that generates a `<style>` block on every page containing `--dynamo-*` CSS custom properties derived from Customizer settings. The static stylesheet references only these custom properties — no hardcoded colour or spacing values. A transient cache ensures the CSS string is only regenerated when settings change. A filter-per-module architecture (`dynamo_css_{module}`) lets a companion premium plugin extend or override any CSS group without patching the theme. The block editor palette stays in sync via the `wp_theme_json_data_theme` filter. Structural and behavioural settings live on a React-powered admin options page built with `@wordpress/scripts`.

## User Stories

1. As a site owner, I want to change the primary colour in the Customizer and see it update live in the preview, so that I can pick the right brand colour without publishing first.
2. As a site owner, I want my chosen colours to appear in the block editor colour palette, so that I can use them when writing content without manually re-entering hex values.
3. As a site owner, I want to set a global border radius so that all buttons and cards share a consistent shape.
4. As a site owner, I want to control body font family and size from the Customizer, so that I can match my brand typography without touching code.
5. As a site owner, I want to set heading typography (font, size, weight, line-height) per heading level, so that my content hierarchy is visually clear.
6. As a site owner, I want to control container max-width and content width from the Customizer, so that my layout fits my design brief.
7. As a site owner, I want to set sidebar width from the Customizer, so that I can balance content and sidebar proportions.
8. As a site owner, I want to control spacing (padding/margin) for the header, footer, and content area, so that the page breathes correctly on all screen sizes.
9. As a site owner, I want to set box shadow tokens from the Customizer, so that cards and elevated elements feel consistent.
10. As a site owner, I want my Customizer changes to be reflected immediately on the front end without a full page rebuild, so that I can iterate quickly.
11. As a site owner, I want an admin options page where I can toggle theme features on or off, so that I only load what my site needs.
12. As a site owner, I want to choose a layout mode (full-width, boxed, sidebar-left, sidebar-right) from the options page, so that my site structure matches my content plan.
13. As a site owner, I want performance settings (e.g. disable Google Fonts loading) on the options page, so that I can optimise page speed.
14. As a developer, I want every CSS group to expose a `dynamo_css_{module}` filter, so that I can append or override styles from a child theme or plugin without editing core files.
15. As a developer, I want the Token Registry to expose a `dynamo_token_defaults` filter, so that I can change default values programmatically.
16. As a developer, I want Customizer settings to be registered via a documented API, so that I can add custom controls from a companion plugin.
17. As a developer, I want the CSS cache to be busted programmatically via `dynamo_bust_css_cache()`, so that I can trigger regeneration from deployment scripts or other plugins.
18. As a premium plugin author, I want the free theme's filter hooks to remain stable across minor versions, so that my plugin does not break on theme updates.
19. As a premium plugin author, I want to register additional CSS modules on `dynamo_css_modules` so that my extra controls are included in the generated style block.
20. As an end user on a slow connection, I want the theme to deliver CSS as a single inline style block rather than an additional HTTP request, so that the page renders faster.
21. As a content editor, I want the block editor to reflect the active colour palette and typography scale, so that what I see in the editor matches the published front end.
22. As a site owner, I want to set link colours separately from body text, so that links are always distinguishable.
23. As a site owner, I want to set a background colour and an alternate section background colour, so that I can create visual rhythm between page sections.
24. As a site owner, I want secondary and accent colours available as Customizer controls, so that calls-to-action stand out from the primary brand colour.
25. As a site owner, I want all archive, search, single post, and static page templates to respect my dynamic tokens, so that the design is consistent across content types.

## Implementation Decisions

### Modules

**Token Registry**
Owns all design token definitions and their defaults. Exposes `get(key)` and `all()`. Tokens are grouped by module (colors, typography, spacing, layout, borders, shadows). Defaults filterable via `dynamo_token_defaults`. This is the single source of truth — no other module hardcodes token values.

**CSS Generator**
Accepts the full token set from the Token Registry. Iterates over registered CSS modules, passing each module's tokens through `apply_filters("dynamo_css_{$module}", '', $tokens)`. Concatenates all returned CSS fragments. Exposes a single `generate()` method. Modules register themselves on `dynamo_css_modules` so the generator's module list is itself filterable.

**CSS Cache**
Wraps WordPress transients. `get()` returns the cached CSS string or `null`. `set($css)` writes to the transient. `bust()` deletes the transient and is called on `customize_save_after`. Also exposes `dynamo_bust_css_cache()` as a global helper for external callers. Cache key includes a theme version hash to auto-invalidate on theme updates.

**CSS Output**
Thin orchestrator hooked to `wp_head`. Calls `Cache::get()`; on miss, calls `Generator::generate()` then `Cache::set()`. Prints the CSS string wrapped in `<style id="dynamo-dynamic-css">`.

**Per-module CSS Generators (6 modules)**
Each module is a class implementing a single static method that receives the full token set and returns a CSS string fragment. The fragment writes `--dynamo-{module}-{token}` custom properties to `:root` and any selector-scoped rules that reference them. Modules: `Colors`, `Typography`, `Spacing`, `Layout`, `Borders`, `Shadows`. Each registers on its corresponding `dynamo_css_{module}` filter at `functions.php` bootstrap.

**Customizer Integration**
Registers one Customizer panel per module. Each panel contains sections and controls mapped 1-to-1 to Token Registry keys. On Customizer live preview, uses `postMessage` transport where possible. Calls `Cache::bust()` on `customize_save_after`.

**theme.json Sync**
Hooks `wp_theme_json_data_theme`. Reads current token values from the Token Registry. Injects colour palette entries and typography presets into the theme.json data array. Pure array-in / array-out transform — no side effects.

**Admin Options Page**
PHP backend: registers a top-level admin menu page, stores settings via the WordPress Settings API under a single `dynamo_options` option key. React frontend: built with `@wordpress/scripts`, uses `@wordpress/components` (ToggleControl, SelectControl, TextControl, TabPanel). Communicates with the backend via the WordPress REST API (`/wp/v2/settings` or a custom namespace endpoint). Organised into tabs: Layout, Features, Performance.

**Template Layer**
Standard WordPress PHP templates: `index.php`, `single.php`, `page.php`, `archive.php`, `search.php`, `404.php`, `header.php`, `footer.php`. All templates reference only `--dynamo-*` custom properties via the static stylesheet — no inline style attributes.

### Architectural decisions

- CSS custom properties are the only bridge between the dynamic CSS system and the static stylesheet. The static stylesheet contains zero hardcoded colour, typography, or spacing values.
- `theme.json` defines structural block editor configuration (spacing scale, layout, shadow presets) but colour palette and typography presets are injected at runtime via the `wp_theme_json_data_theme` filter, not hardcoded.
- The free/premium boundary is enforced entirely through filters. The free theme never checks for a premium plugin; the premium plugin hooks in non-destructively.
- Minimum requirements: WordPress 6.0, PHP 8.0.

## Testing Decisions

**What makes a good test:** Tests assert on observable output (the generated CSS string, the token value returned, the cache state) — not on internal implementation details like which private method was called or how many times a filter fired. Each test sets up its inputs, calls the public interface, and asserts on the result.

**Modules with tests:**

- **Token Registry** — assert correct default values are returned for each token key; assert that the `dynamo_token_defaults` filter can override a value; assert unknown keys return `null` or a defined fallback.
- **CSS Generator** — assert that `generate()` returns a string containing `--dynamo-colors-primary` given a known token set; assert that a filter registered on `dynamo_css_colors` can append extra CSS; assert that an empty module list returns an empty string.
- **CSS Cache** — assert `get()` returns `null` on a cold cache; assert `get()` returns the stored string after `set()`; assert `get()` returns `null` after `bust()`; assert the cache key changes when the theme version changes.
- **Per-module CSS generators (all 6)** — for each module, assert that given a known token set the output string contains the expected `--dynamo-{module}-{token}: {value}` declarations; assert that the output is valid CSS (no unclosed braces); assert that an empty token set returns an empty string or a safe fallback.
- **theme.json Sync** — assert that given a token set containing a known primary colour, the injected theme.json data includes that colour in the palette array; assert that existing palette entries not managed by Dynamo are preserved; assert the transform is idempotent.

Prior art: WordPress theme unit tests typically use PHPUnit with the `WP_UnitTestCase` base class and a local test database. Brain Monkey or WP_Mock can stub WordPress functions for unit tests that don't need the full WP stack.

## Out of Scope

- WooCommerce templates
- Blog-specific templates (`home.php`, `category.php`, `tag.php`)
- Per-post / per-page CSS overrides
- Static file CSS generation (write-to-disk mode)
- Premium plugin implementation (covered in a separate PRD)
- Multisite support
- RTL stylesheet
- Full Site Editing / block theme templates
- Accessibility audit beyond WCAG AA colour contrast on default tokens
- Translation/localisation beyond registering the `dynamo` text domain

## Further Notes

- The companion premium plugin is a separate project and will have its own PRD. The free theme must never `require` or check for the premium plugin directly.
- WordPress.org theme review guidelines prohibit upsells inside the theme UI. The admin options page must not advertise the premium plugin.
- The `dynamo_css_{module}` filter signature must be considered public API from v1.0.0. Breaking changes require a major version bump.
- Dynamo should ship with a complete `readme.txt` in WordPress.org format and pass the Theme Check plugin with zero errors and zero warnings before any public release.
