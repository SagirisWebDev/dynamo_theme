<?php
declare(strict_types=1);

class Dynamo_Consent_Placeholder {

    private const EMBED_MAP = [
        'youtube.com'      => ['service' => 'YouTube', 'category' => 'marketing'],
        'youtu.be'         => ['service' => 'YouTube', 'category' => 'marketing'],
        'player.vimeo.com' => ['service' => 'Vimeo',   'category' => 'statistics'],
        'vimeo.com'        => ['service' => 'Vimeo',   'category' => 'statistics'],
    ];

    public static function replace_embeds(string $content): string {
        return preg_replace_callback(
            '/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<\/iframe>/si',
            static function (array $matches): string {
                $src  = $matches[1];
                $info = self::detect_service($src);
                if ($info === null) {
                    return $matches[0];
                }
                if (apply_filters('dynamo_has_consent', false, $info['category'])) {
                    return $matches[0];
                }
                return self::render_placeholder($info['service'], $info['category'], $matches[0]);
            },
            $content
        ) ?? $content;
    }

    private static function detect_service(string $src): ?array {
        foreach (self::EMBED_MAP as $domain => $info) {
            if (str_contains($src, $domain)) {
                return $info;
            }
        }
        return null;
    }

    public static function render_placeholder(string $service_name, string $consent_category, string $embed_html = ''): string {
        $template = locate_template('templates/consent-placeholder.php');
        if ($template === '') {
            $template = DYNAMO_PATH . 'templates/consent-placeholder.php';
        }
        ob_start();
        include $template;
        return ob_get_clean() ?: '';
    }
}
