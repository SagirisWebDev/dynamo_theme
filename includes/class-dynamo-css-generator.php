<?php
declare(strict_types=1);

class Dynamo_CSS_Generator {

    private Dynamo_Token_Registry $registry;
    private Dynamo_Font_Manifest $fonts;

    public function __construct(Dynamo_Token_Registry $registry, Dynamo_Font_Manifest $fonts) {
        $this->registry = $registry;
        $this->fonts    = $fonts;
    }

    public function generate(): string {
        $modules = apply_filters(
            'dynamo_css_modules',
            ['colors', 'typography', 'spacing', 'layout', 'borders', 'shadows', 'woocommerce']
        );

        if (empty($modules)) {
            return '';
        }

        $parts            = [];
        $woocommerce_seen = false;
        foreach ($modules as $module) {
            $declarations = $this->module_declarations($module);
            $declarations = apply_filters("dynamo_css_{$module}", $declarations, $this->registry);
            if ('' !== $declarations) {
                $parts[] = $declarations;
                if ($module === 'woocommerce') {
                    $woocommerce_seen = true;
                }
            }
        }

        $root  = empty($parts) ? '' : ":root {\n" . implode("\n", $parts) . "\n}";
        $rules = $woocommerce_seen ? $this->generate_woocommerce_rules() : '';

        if ($root === '' && $rules === '') {
            return '';
        }

        return trim($root . ($rules !== '' ? "\n\n" . $rules : ''));
    }

    public function generate_woocommerce_rules(): string {
        return <<<CSS
.woocommerce span.onsale {
  background-color: var(--dynamo-woocommerce-sale-badge-bg);
  color: var(--dynamo-woocommerce-sale-badge-color);
}
.woocommerce .star-rating,
.woocommerce .star-rating::before,
.woocommerce .star-rating span::before {
  color: var(--dynamo-woocommerce-star-color);
}
.woocommerce a.button.add_to_cart_button,
.woocommerce button.single_add_to_cart_button,
.woocommerce-page button.single_add_to_cart_button {
  background-color: var(--dynamo-colors-primary);
  color: var(--dynamo-colors-background);
}
.woocommerce ul.products li.product {
  background-color: var(--dynamo-colors-background);
  border: var(--dynamo-borders-width) solid var(--dynamo-borders-color);
  border-radius: var(--dynamo-borders-radius);
  box-shadow: var(--dynamo-shadows-md);
}
CSS;
    }

    // Last-resort fallback when a typography token references a slug
    // that is not in the manifest. Same shape as the manifest's own
    // baked-in safety fallback so the site always renders a real stack.
    private const UNKNOWN_SLUG_FALLBACK = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    private function module_declarations(string $module): string {
        $lines = [];
        foreach ($this->registry->all() as $key => $value) {
            if (str_starts_with($key, $module)) {
                $setting_id = 'dynamo_' . str_replace('-', '_', $key);
                $saved      = get_theme_mod($setting_id);
                if ('' !== $saved && false !== $saved) {
                    $value = $saved;
                }
                if (str_ends_with($key, '-font-family')) {
                    $value = $this->resolve_font_family((string) $value, $key);
                }
                $lines[] = "  --dynamo-{$key}: {$value};";
            }
        }
        return implode("\n", $lines);
    }

    private function resolve_font_family(string $slug, string $token_key): string {
        $entry = $this->fonts->get($slug);
        if ($entry === null) {
            _doing_it_wrong(
                __METHOD__,
                sprintf(
                    'Typography token "%s" references unknown font slug "%s". Falling back to system stack.',
                    $token_key,
                    $slug
                ),
                '1.0.0'
            );
            return self::UNKNOWN_SLUG_FALLBACK;
        }
        $fallback = $entry['fallback'] ?? 'sans-serif';
        $faces    = $entry['faces'] ?? [];
        if (empty($faces)) {
            return $fallback;
        }
        $label = $entry['label'] ?? $slug;
        return '"' . $label . '", ' . $fallback;
    }
}
