# Changelog

## [Unreleased] — v1.0.0

### Stable public hook surface

The following filters and helper function are now considered stable API. Breaking changes require a major version bump.

#### Filters

- **`dynamo_token_defaults`** — Filters the associative array of design token defaults (`array<string, string>`). Registered in `Dynamo_Token_Registry::all()`.
- **`dynamo_css_modules`** — Filters the ordered list of CSS module slugs the generator iterates over (`string[]`). Registered in `Dynamo_CSS_Generator::generate()`.
- **`dynamo_css_{$module}`** — One filter per module slug (default: `colors`, `typography`, `spacing`, `layout`, `borders`, `shadows`). Filters the CSS declaration block string for that module. Receives `(string $declarations, Dynamo_Token_Registry $registry)`.

#### Functions

- **`dynamo_bust_css_cache()`** — Deletes the cached CSS transient, forcing regeneration on the next page load.
