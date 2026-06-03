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
            ['colors', 'typography', 'spacing', 'layout', 'borders', 'shadows', 'header', 'woocommerce']
        );

        $variable_chunks  = [];
        $woocommerce_seen = false;
        if (!empty($modules)) {
            foreach ($modules as $module) {
                $declarations = $this->module_declarations($module);
                $declarations = apply_filters("dynamo_css_{$module}", $declarations, $this->registry);
                if ('' !== $declarations) {
                    $variable_chunks[] = $declarations;
                    if ($module === 'woocommerce') {
                        $woocommerce_seen = true;
                    }
                }
            }
        }

        $rule_block = $woocommerce_seen ? $this->generate_woocommerce_rules() : '';
        if (class_exists('Dynamo_Binding_Registry') && class_exists('Dynamo_Binding_CSS_Renderer')) {
            $renderer      = new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance());
            $binding_vars  = $renderer->variable_lines();
            $binding_rules = $renderer->rule_lines();
            if (!empty($binding_vars)) {
                $variable_chunks[] = implode("\n", $binding_vars);
            }
            if (!empty($binding_rules)) {
                $rule_block .= ($rule_block !== '' ? "\n\n" : '') . implode("\n", $binding_rules);
            }
        }

        if (empty($variable_chunks) && '' === $rule_block) {
            return '';
        }

        $root_block = empty($variable_chunks) ? '' : ":root {\n" . implode("\n", $variable_chunks) . "\n}";

        if ('' === $rule_block) {
            return $root_block;
        }
        if ('' === $root_block) {
            return $rule_block;
        }
        return $root_block . "\n\n" . $rule_block;
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
            .woocommerce button.button.single_add_to_cart_button,
            .woocommerce-page button.button.single_add_to_cart_button {
            background-color: var(--dynamo-woocommerce-add-to-cart-bg);
            color: var(--dynamo-woocommerce-add-to-cart-color);
            }
            .woocommerce div.product p.price,
            .woocommerce div.product span.price,
            .woocommerce-page div.product p.price,
            .woocommerce-page div.product span.price {
            color: var(--dynamo-woocommerce-single-price-color);
            }
            .woocommerce ul.products li.product .price,
            .woocommerce-page ul.products li.product .price {
            color: var(--dynamo-woocommerce-loop-price-color);
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
        if ($module === 'shadows') {
            return $this->shadows_declarations();
        }

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

    private function shadows_declarations(): string {
        $lines = [];
        foreach (['sm', 'md'] as $size) {
            $length  = $this->resolve_value("shadows-{$size}-length");
            $color   = $this->resolve_value("shadows-{$size}-color");
            $opacity = $this->resolve_value("shadows-{$size}-opacity");
            if ($length === '' || $color === '') {
                continue;
            }
            $rgb = $this->hex_to_rgb_triplet($color);
            $color_fn = "rgb({$rgb} / {$opacity})";
            $layers   = array_map(
                fn($layer) => trim($layer) . ' ' . $color_fn,
                explode(',', $length)
            );
            $lines[] = "  --dynamo-shadows-{$size}: " . implode(', ', $layers) . ';';
        }
        return implode("\n", $lines);
    }

    private function resolve_value(string $token): string {
        $value      = $this->registry->get($token) ?? '';
        $setting_id = 'dynamo_' . str_replace('-', '_', $token);
        $saved      = get_theme_mod($setting_id);
        if ('' !== $saved && false !== $saved) {
            $value = $saved;
        }
        return (string) $value;
    }

    private function hex_to_rgb_triplet(string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return '0 0 0';
        }
        return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
    }
}
