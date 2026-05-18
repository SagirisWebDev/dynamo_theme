<?php
declare(strict_types=1);

define('DYNAMO_VERSION', '1.0.0');
define('DYNAMO_PATH', dirname(__DIR__) . '/');
define('DYNAMO_URL', 'http://localhost/');
define('DAY_IN_SECONDS', 86400);

$GLOBALS['wp_filter']             = [];
$GLOBALS['wp_transients']         = [];
$GLOBALS['wp_theme_mods']         = [];
$GLOBALS['wp_theme_pages']        = [];
$GLOBALS['wp_registered_settings'] = [];
$GLOBALS['wp_enqueued_scripts']   = [];
$GLOBALS['wp_enqueued_styles']    = [];
$GLOBALS['wp_theme_supports']     = [];
$GLOBALS['wp_removed_actions']    = [];

function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
    $GLOBALS['wp_filter'][$tag][$priority][] = $callback;
}

function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
    add_filter($tag, $callback, $priority, $accepted_args);
}

function apply_filters(string $tag, mixed $value, mixed ...$args): mixed {
    if (empty($GLOBALS['wp_filter'][$tag])) {
        return $value;
    }
    ksort($GLOBALS['wp_filter'][$tag]);
    foreach ($GLOBALS['wp_filter'][$tag] as $callbacks) {
        foreach ($callbacks as $callback) {
            $value = $callback($value, ...$args);
        }
    }
    return $value;
}

function get_transient(string $key): mixed {
    return $GLOBALS['wp_transients'][$key] ?? false;
}

function set_transient(string $key, mixed $value, int $expiration = 0): bool {
    $GLOBALS['wp_transients'][$key] = $value;
    return true;
}

function delete_transient(string $key): bool {
    unset($GLOBALS['wp_transients'][$key]);
    return true;
}

function __( string $text, string $domain = 'default' ): string {
    return $text;
}

function sanitize_hex_color( string $color ): string {
    return $color;
}

function wp_enqueue_script(string $handle, string $src = '', array $deps = [], mixed $ver = false, bool $in_footer = false): void {
    $GLOBALS['wp_enqueued_scripts'][] = $handle;
    $GLOBALS['wp_enqueued_script_deps'][$handle] = $deps;
}

function wp_enqueue_style(string $handle = '', string $src = '', array $deps = [], mixed $ver = false, string $media = 'all'): void {
    if ($handle !== '') {
        $GLOBALS['wp_enqueued_styles'][] = $handle;
    }
}

function add_theme_support(string $feature, mixed ...$args): bool {
    $GLOBALS['wp_theme_supports'][$feature] = $args === [] ? true : $args;
    return true;
}

function current_theme_supports(string $feature): bool {
    return array_key_exists($feature, $GLOBALS['wp_theme_supports'] ?? []);
}

function is_woocommerce(): bool {
    return (bool) ($GLOBALS['wp_is_woocommerce'] ?? false);
}

function is_cart(): bool {
    return (bool) ($GLOBALS['wp_is_cart'] ?? false);
}

function is_checkout(): bool {
    return (bool) ($GLOBALS['wp_is_checkout'] ?? false);
}

function is_account_page(): bool {
    return (bool) ($GLOBALS['wp_is_account_page'] ?? false);
}

function wc_get_cart_url(): string {
    return $GLOBALS['wc_cart_url'] ?? 'http://localhost/cart/';
}

function WC(): object {
    return new class {
        public object $cart;
        public function __construct() {
            $this->cart = new class {
                public function get_cart_contents_count(): int {
                    return (int) ($GLOBALS['wc_cart_count'] ?? 0);
                }
            };
        }
    };
}

function add_theme_page(string $page_title, string $menu_title, string $capability, string $menu_slug, ?callable $callback = null): void {
    $GLOBALS['wp_theme_pages'][] = $menu_slug;
}

function register_setting(string $option_group, string $option_name, array $args = []): void {
    $GLOBALS['wp_registered_settings'][] = ['group' => $option_group, 'name' => $option_name];
}

function plugin_dir_url(): string { return DYNAMO_URL; }

function remove_action(string $tag, mixed $callback = null, int $priority = 10): bool {
    $GLOBALS['wp_removed_actions'][]      = $tag;
    $GLOBALS['wp_removed_action_specs'][] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
    return true;
}

function remove_filter(string $tag, mixed $callback = null, int $priority = 10): bool {
    $GLOBALS['wp_removed_actions'][]      = $tag;
    $GLOBALS['wp_removed_action_specs'][] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
    return true;
}

function wp_deregister_script(string $handle): void {
    $GLOBALS['wp_deregistered_scripts'][] = $handle;
}

function wp_dequeue_script(string $handle): void {
    $GLOBALS['wp_dequeued_scripts'][] = $handle;
}

function get_option(string $option, mixed $default = false): mixed {
    return $GLOBALS['wp_options'][$option] ?? $default;
}

function get_theme_mod(string $name, mixed $default = false): mixed {
    return $GLOBALS['wp_theme_mods'][$name] ?? $default;
}

function _doing_it_wrong(string $function_name, string $message, string $version): void {
    $GLOBALS['wp_doing_it_wrong'][] = compact('function_name', 'message', 'version');
}

function current_user_can(string $capability): bool {
    return (bool) ($GLOBALS['wp_current_user_can'][$capability] ?? false);
}

function esc_html__(string $text, string $domain = 'default'): string {
    return $text;
}

function wp_kses_post(string $data): string {
    return $data;
}


function update_option(string $option, mixed $value): bool {
    $GLOBALS['wp_options'][$option] = $value;
    $GLOBALS['wp_update_option_calls'][] = $option;
    return true;
}

function is_front_page(): bool {
    return (bool) ($GLOBALS['wp_is_front_page'] ?? false);
}

function home_url(string $path = ''): string {
    return 'http://localhost/' . ltrim($path, '/');
}

function esc_url(string $url): string {
    return $url;
}

function esc_html(string $text): string {
    return $text;
}

function esc_attr(string $text): string {
    return $text;
}

function esc_attr__(string $text, string $domain = 'default'): string {
    return $text;
}

class WP_Customize_Color_Control {
    public string $id;
    public array  $args;

    public function __construct(object $manager, string $id, array $args) {
        $this->id   = $id;
        $this->args = $args;
    }
}

class WP_Customize_Control {
    public string $id;
    public array  $args;

    public function __construct(object $manager, string $id, array $args) {
        $this->id   = $id;
        $this->args = $args;
    }
}

class WP_Theme_JSON_Data {
    private array $data;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function get_data(): array {
        return $this->data;
    }

    public function update_with(array $data): void {
        $this->data = $data;
    }
}

require_once DYNAMO_PATH . 'includes/class-dynamo-token-registry.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-font-manifest.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-font-renderer.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-options.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-breadcrumbs.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-css-generator.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-css-cache.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-customizer.php';
require_once DYNAMO_PATH . 'includes/class-dynamo-theme-json-sync.php';
require_once DYNAMO_PATH . 'includes/woocommerce/class-dynamo-woocommerce.php';
require_once __DIR__ . '/MakesCustomizer.php';
require_once __DIR__ . '/FakeCustomizeManager.php';

function dynamo_bust_css_cache(): void {
    (new Dynamo_CSS_Cache())->bust();
}
