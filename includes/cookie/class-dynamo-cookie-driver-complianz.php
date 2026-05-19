<?php
declare(strict_types=1);

require_once __DIR__ . '/class-dynamo-consent-placeholder.php';

class Dynamo_Cookie_Driver_Complianz implements Dynamo_Cookie_Driver {

    public function register_palette_sync_hooks(): void {
        // Store a baseline hash on the first admin page load so the option
        // exists before any Customizer save fires. Without this, the very
        // first save after deployment always writes to Complianz because
        // get_option returns false and the === comparison fails.
        add_action('admin_init', function (): void {
            if (get_option('dynamo_complianz_palette_hash') === false) {
                $registry = new Dynamo_Token_Registry();
                update_option(
                    'dynamo_complianz_palette_hash',
                    md5(serialize($this->build_palette($registry))),
                    false
                );
            }
        });

        add_action('customize_save_after', function (): void {
            $registry = new Dynamo_Token_Registry();
            $palette  = $this->build_palette($registry);

            $new_hash = md5(serialize($palette));
            if (get_option('dynamo_complianz_palette_hash') === $new_hash) {
                return;
            }

            $this->write_palette_to_db($palette);
            $this->clear_complianz_css_cache();
            update_option('dynamo_complianz_palette_hash', $new_hash, false);
        });
    }

    public function build_palette(Dynamo_Token_Registry $registry): array {
        $map     = apply_filters('dynamo_complianz_colorpalette', $this->default_palette_map());
        $palette = [];
        foreach ($map as $field => $subkeys) {
            foreach ($subkeys as $subkey => $token) {
                $value = $registry->get($token);
                if ($value !== null) {
                    $palette[$field][$subkey] = $value;
                }
            }
        }
        return $palette;
    }

    private function default_palette_map(): array {
        return [
            'colorpalette_background' => [
                'color'  => 'colors-background',
                'border' => 'borders-color',
            ],
            'colorpalette_text' => [
                'color'     => 'colors-text',
                'hyperlink' => 'colors-link',
            ],
            'colorpalette_toggles' => [
                'background' => 'colors-primary',
                'bullet'     => 'colors-background',
                'inactive'   => 'colors-accent',
            ],
            'colorpalette_button_accept' => [
                'background' => 'colors-primary',
                'border'     => 'colors-primary',
                'text'       => 'colors-background',
            ],
            'colorpalette_button_deny' => [
                'background' => 'colors-background',
                'border'     => 'colors-secondary',
                'text'       => 'colors-text',
            ],
            'colorpalette_button_settings' => [
                'background' => 'colors-background',
                'border'     => 'colors-secondary',
                'text'       => 'colors-text',
            ],
        ];
    }

    private function write_palette_to_db(array $palette): void {
        global $wpdb;
        $ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}cmplz_cookiebanners");
        foreach ($ids ?: [] as $id) {
            foreach ($palette as $field => $data) {
                $wpdb->update(
                    $wpdb->prefix . 'cmplz_cookiebanners',
                    [$field => serialize($data)],
                    ['id' => (int) $id]
                );
            }
        }
    }

    private function clear_complianz_css_cache(): void {
        if (!function_exists('wp_upload_dir')) {
            return;
        }
        $upload_dir = wp_upload_dir();
        $css_dir    = rtrim($upload_dir['basedir'], '/') . '/complianz/css/';
        foreach (glob($css_dir . 'banner-*.css') ?: [] as $file) {
            @unlink($file);
        }
        if (function_exists('wp_cache_delete_group')) {
            wp_cache_delete_group('cmplz');
        }
    }

    public function register_embed_hooks(): void {
        add_filter('dynamo_has_consent', static function (bool $_, string $category): bool {
            return function_exists('cmplz_has_consent') && cmplz_has_consent($category);
        }, 10, 2);
        add_filter('the_content', static function (string $content): string {
            return Dynamo_Consent_Placeholder::replace_embeds($content);
        });
        if (class_exists('WooCommerce')) {
            add_filter('woocommerce_short_description', static function (string $content): string {
                return Dynamo_Consent_Placeholder::replace_embeds($content);
            });
            add_filter('term_description', static function (string $content): string {
                if (! is_product_category() && ! is_product_tag()) {
                    return $content;
                }
                return Dynamo_Consent_Placeholder::replace_embeds($content);
            });
        }
        add_action('wp_enqueue_scripts', static function (): void {
            wp_enqueue_script(
                'dynamo-consent-reveal',
                DYNAMO_URL . 'assets/js/consent-reveal.js',
                [],
                DYNAMO_VERSION,
                true
            );
        });
    }

    public function get_consent_categories(): array {
        return [
            ['slug' => 'marketing',   'label' => 'Marketing'],
            ['slug' => 'statistics',  'label' => 'Statistics'],
            ['slug' => 'functional',  'label' => 'Functional'],
            ['slug' => 'preferences', 'label' => 'Preferences'],
        ];
    }
}
