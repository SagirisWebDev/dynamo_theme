<?php
declare(strict_types=1);

class Dynamo_Token_Registry {

    /**
     * Width scale steps that alias an existing role token rather than storing
     * their own value. Key = alias token, value = role token it resolves to.
     * Resolved at read time so they always reflect the current role token value.
     */
    private const ALIASES = [
        'layout-width-default'   => 'layout-content-width',
        'layout-width-container' => 'layout-container-max-width',
    ];

    private array $defaults = [
        'colors-primary'     => '#3b82f6',
        'colors-secondary'   => '#6b7280',
        'colors-accent'      => '#f59e0b',
        'colors-background'  => '#ffffff',
        'colors-text'        => '#111827',
        'colors-link'        => '#2563eb',
        'colors-section-alt' => '#f3f4f6',

        'typography-body-font-family' => 'system-sans',
        'typography-body-font-size'   => '1rem',
        'typography-body-font-weight' => '400',
        'typography-body-line-height' => '1.6',

        'typography-h1-font-family' => 'system-sans',
        'typography-h1-font-size'   => '2.25rem',
        'typography-h1-font-weight' => '700',
        'typography-h1-line-height' => '1.2',

        'typography-h2-font-family' => 'system-sans',
        'typography-h2-font-size'   => '1.875rem',
        'typography-h2-font-weight' => '700',
        'typography-h2-line-height' => '1.2',

        'typography-h3-font-family' => 'system-sans',
        'typography-h3-font-size'   => '1.5rem',
        'typography-h3-font-weight' => '700',
        'typography-h3-line-height' => '1.2',

        'typography-h4-font-family' => 'system-sans',
        'typography-h4-font-size'   => '1.25rem',
        'typography-h4-font-weight' => '700',
        'typography-h4-line-height' => '1.2',

        'typography-h5-font-family' => 'system-sans',
        'typography-h5-font-size'   => '1.125rem',
        'typography-h5-font-weight' => '600',
        'typography-h5-line-height' => '1.2',

        'typography-h6-font-family' => 'system-sans',
        'typography-h6-font-size'   => '1rem',
        'typography-h6-font-weight' => '600',
        'typography-h6-line-height' => '1.2',

        'spacing-header-padding-top'     => '2rem',
        'spacing-header-padding-bottom'  => '2rem',
        'spacing-footer-padding-top'     => '2rem',
        'spacing-footer-padding-bottom'  => '2rem',
        'spacing-content-padding-top'    => '3rem',
        'spacing-content-padding-bottom' => '3rem',
        'spacing-content-padding-x'      => '1rem',

        'layout-container-max-width' => '1200px',
        'layout-content-width'       => '720px',
        'layout-sidebar-width'       => '300px',
        'layout-width-narrow'        => '640px',
        'layout-width-wide'          => '1024px',
        'layout-width-full'          => '100%',

        'borders-radius'    => '0.375rem',
        'borders-radius-lg' => '0.5rem',
        'borders-color'     => '#e5e7eb',
        'borders-width'     => '1px',

        'shadows-sm-length'  => '0 1px 2px 0',
        'shadows-sm-color'   => '#000000',
        'shadows-sm-opacity' => '0.05',
        'shadows-md-length'  => '0 4px 6px -1px, 0 2px 4px -2px',
        'shadows-md-color'   => '#000000',
        'shadows-md-opacity' => '0.1',

        'header-menu-cart' => 'flex-end',

        'woocommerce-sale-badge-bg'    => '#dc2626',
        'woocommerce-sale-badge-color' => '#ffffff',
        'woocommerce-star-color'       => '#f59e0b',
        'woocommerce-add-to-cart-bg'    => '#3b82f6',
        'woocommerce-add-to-cart-color' => '#ffffff',
        'woocommerce-single-price-color' => '#111827',
        'woocommerce-loop-price-color'   => '#111827',

        'woocommerce-shop-columns'           => '3',
        'woocommerce-shop-products-per-page' => '12',

        'woocommerce-header-cart-enabled'  => '1',
        'woocommerce-header-cart-position' => 'right',

        'woocommerce-single-show-title'        => '1',
        'woocommerce-single-show-price'        => '1',
        'woocommerce-single-show-rating'       => '1',
        'woocommerce-single-show-excerpt'      => '1',
        'woocommerce-single-show-add-to-cart'  => '1',
        'woocommerce-single-show-meta'         => '1',
        'woocommerce-single-related-columns'   => '4',

        'woocommerce-quantity-buttons-enabled' => '1',

        'woocommerce-cart-checkout-button-text' => '',
        'woocommerce-cart-cross-sells-enabled'  => '1',

        'woocommerce-card-show-image'             => '1',
        'woocommerce-card-show-title'             => '1',
        'woocommerce-card-show-price'             => '1',
        'woocommerce-card-show-rating'            => '1',
        'woocommerce-card-show-short-description' => '0',
        'woocommerce-card-show-add-to-cart'       => '1',
    ];

    public function get(string $key): ?string {
        return $this->all()[$key] ?? null;
    }

    public function all(): array {
        $tokens = apply_filters('dynamo_token_defaults', $this->defaults);
        if (function_exists('get_theme_mod')) {
            foreach ($tokens as $token => $default) {
                $saved = get_theme_mod('dynamo_' . str_replace('-', '_', $token), false);
                if ($saved !== false && $saved !== '') {
                    $tokens[$token] = $saved;
                }
            }
        }
        // Resolve alias tokens after theme mods are applied so they always
        // reflect the current role-token value rather than a stale default.
        // Skip if the target is absent or empty (e.g. stripped-down test registries).
        foreach (self::ALIASES as $alias => $target) {
            $resolved = $tokens[$target] ?? '';
            if ('' !== $resolved) {
                $tokens[$alias] = $resolved;
            }
        }
        return $tokens;
    }

    /**
     * Resolve a token key to its effective value, following alias chains.
     * For alias steps (layout-width-default, layout-width-container) this
     * returns the live role-token value. For all other keys it returns the
     * registered value (same as get()).
     */
    public function resolve_alias(string $key): ?string {
        return $this->all()[$key] ?? null;
    }

    /** Returns true when the given token key is an alias of another token. */
    public static function is_alias(string $key): bool {
        return array_key_exists($key, self::ALIASES);
    }
}
