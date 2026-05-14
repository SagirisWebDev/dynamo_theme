<?php
declare(strict_types=1);

class Dynamo_Options {

    private const VALID_LAYOUT_MODES = ['full-width', 'boxed', 'sidebar-left', 'sidebar-right'];

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_setting_group']);
        add_action('rest_api_init', [$this, 'register_rest_setting']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('body_class', [$this, 'add_layout_body_class']);
        add_action('init', [$this, 'apply_performance_settings']);
        add_action('wp_enqueue_scripts', [$this, 'apply_late_performance_settings'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('wp_footer', [$this, 'render_scroll_to_top']);
    }

    public function enqueue_frontend_scripts(): void {
        if (self::is_feature_enabled('scroll_to_top')) {
            wp_enqueue_script(
                'dynamo-scroll-to-top',
                DYNAMO_URL . 'assets/js/scroll-to-top.js',
                [],
                DYNAMO_VERSION,
                true
            );
        }
    }

    public function render_scroll_to_top(): void {
        if (!self::is_feature_enabled('scroll_to_top')) {
            return;
        }
        echo '<button type="button" class="dynamo-scroll-top" aria-label="' . esc_attr__('Scroll to top', 'dynamo') . '" aria-hidden="true" tabindex="-1">&uarr;</button>';
    }

    public function register_menu(): void {
        add_theme_page(
            __('Dynamo Options', 'dynamo'),
            __('Dynamo Options', 'dynamo'),
            'manage_options',
            'dynamo-options',
            [$this, 'render_page']
        );
    }

    public function register_setting_group(): void {
        register_setting('dynamo_options_group', 'dynamo_options', [
            'sanitize_callback' => [$this, 'sanitize'],
        ]);
    }

    public function register_rest_setting(): void {
        register_setting('dynamo_options_group', 'dynamo_options', [
            'type'              => 'object',
            'show_in_rest'      => [
                'schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'layout_mode' => ['type' => 'string'],
                        'features'    => [
                            'type' => 'object',
                            'properties' => [
                                'sticky_header' => ['type' => 'boolean'],
                                'breadcrumbs'     => ['type' => 'boolean'],
                                'scroll_to_top'   => ['type' => 'boolean'],
                            ]
                        ],
                        'performance' => [
                            'type' => 'object',
                            'properties' => [
                                'disable_google_fonts' => ['type' => 'boolean'],
                                'disable_emoji'        => ['type' => 'boolean'],
                                'remove_jquery_migrate' => ['type' => 'boolean'],
                            ]
                        ],
                    ],
                ],
            ],
            'sanitize_callback' => [$this, 'sanitize'],
        ]);
    }

    public function enqueue_scripts(string $hook): void {
        if ($hook !== 'appearance_page_dynamo-options') {
            return;
        }
        
        $deps = include_once DYNAMO_PATH . '/assets/js/options/options.asset.php';
        wp_enqueue_script(
            'dynamo-options/',
            DYNAMO_URL . 'assets/js/options/options.js',
            $deps['dependencies'],
            $deps['version'],
            true
        );
    }

    public static function get_layout_mode(): string {
        $options = get_option('dynamo_options', []);
        $mode    = $options['layout_mode'] ?? 'full-width';
        return in_array($mode, self::VALID_LAYOUT_MODES, true) ? $mode : 'full-width';
    }

    public static function is_performance_enabled(string $key): bool {
        $options = get_option('dynamo_options', []);
        $perf    = $options['performance'] ?? [];
        return (bool) ($perf[$key] ?? false);
    }

    public static function is_feature_enabled(string $feature): bool {
        $options  = get_option('dynamo_options', []);
        $features = $options['features'] ?? [];
        return (bool) ($features[$feature] ?? true);
    }

    public function add_layout_body_class(array $classes): array {
        $classes[] = 'dynamo-layout-' . self::get_layout_mode();
        return $classes;
    }

    public function apply_performance_settings(): void {
        if (self::is_performance_enabled('disable_google_fonts')) {
            add_filter('style_loader_src', [$this, 'block_google_fonts_src'], 99, 2);
            // Catch raw <link> tags echoed into wp_head that bypass the style loader
            // (e.g. plugin output, hand-written preload hints). The buffer is only
            // installed when this toggle is on, so opted-out users pay no overhead.
            add_action('wp_head', [$this, 'start_head_buffer'], 0);
            add_action('wp_head', [$this, 'flush_head_buffer'], PHP_INT_MAX);
        }
        if (self::is_performance_enabled('disable_emoji')) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        }
    }

    public function apply_late_performance_settings(): void {
        // jquery-migrate is registered by wp_default_scripts during script loading,
        // so dequeue/deregister must run after that — wp_enqueue_scripts is the
        // earliest hook where the handle is reliably present.
        if (self::is_performance_enabled('remove_jquery_migrate')) {
            wp_dequeue_script('jquery-migrate');
            wp_deregister_script('jquery-migrate');
        }
    }

    public function block_google_fonts_src(string $src, string $handle): string {
        if (str_contains($src, 'fonts.googleapis.com')) {
            return '';
        }
        return $src;
    }

    public function start_head_buffer(): void {
        ob_start();
    }

    public function flush_head_buffer(): void {
        $html = ob_get_clean();
        if (!is_string($html)) {
            return;
        }
        echo $this->strip_google_font_link_tags($html);
    }

    public function strip_google_font_link_tags(string $html): string {
        return (string) preg_replace(
            '#<link\b[^>]*\bhref\s*=\s*[\'"][^\'"]*fonts\.(?:googleapis|gstatic)\.com[^\'"]*[\'"][^>]*/?>#i',
            '',
            $html
        );
    }

    public function render_page(): void {
        echo '<div id="dynamo-options-root"></div>';
    }

    public function sanitize(mixed $input): array {
        $data        = is_array($input) ? $input : [];
        $mode        = $data['layout_mode'] ?? 'full-width';
        $sanitized   = [];

        $sanitized['layout_mode'] = in_array($mode, self::VALID_LAYOUT_MODES, true)
            ? $mode
            : 'full-width';

        $raw_features = isset($data['features']) && is_array($data['features'])
            ? $data['features']
            : [];

        $sanitized['features'] = array_map('boolval', $raw_features);

        $raw_perf = isset($data['performance']) && is_array($data['performance'])
            ? $data['performance']
            : [];

        $sanitized['performance'] = array_map('boolval', $raw_perf);

        return $sanitized;
    }
}
