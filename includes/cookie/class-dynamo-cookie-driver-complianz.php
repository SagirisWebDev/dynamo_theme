<?php
declare(strict_types=1);

require_once __DIR__ . '/class-dynamo-consent-placeholder.php';

class Dynamo_Cookie_Driver_Complianz implements Dynamo_Cookie_Driver {

    public function register_palette_sync_hooks(): void {
        add_filter('cmplz_banner_css', static function (string $css): string {
            $map = apply_filters('dynamo_cookie_banner_tokens', [
                '--cookie-primary'     => 'colors-primary',
                '--cookie-background'  => 'colors-background',
                '--cookie-text'        => 'colors-text',
                '--cookie-link'        => 'colors-link',
                '--cookie-font-family' => 'typography-body-font-family',
            ]);
            $registry = new Dynamo_Token_Registry();
            $props    = '';
            foreach ($map as $prop => $token) {
                $value = $registry->get($token);
                if ($value !== null) {
                    $props .= "{$prop}:{$value};";
                }
            }
            return $props !== '' ? $css . ":root{{$props}}" : $css;
        });
    }

    public function register_embed_hooks(): void {
        add_filter('dynamo_has_consent', static function (bool $_, string $category): bool {
            return function_exists('cmplz_has_consent') && cmplz_has_consent($category);
        }, 10, 2);
        add_filter('the_content', static function (string $content): string {
            return Dynamo_Consent_Placeholder::replace_embeds($content);
        });
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
