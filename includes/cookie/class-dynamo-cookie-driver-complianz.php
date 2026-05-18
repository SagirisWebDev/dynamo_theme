<?php
declare(strict_types=1);

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
        // Stub — wired in a subsequent issue.
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
