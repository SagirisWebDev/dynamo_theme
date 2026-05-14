<?php
declare(strict_types=1);

define('DYNAMO_VERSION', '1.0.0');
define('DYNAMO_PATH', get_template_directory());
define('DYNAMO_URL', trailingslashit(get_template_directory_uri()));

require_once DYNAMO_PATH . '/includes/class-dynamo-token-registry.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-font-manifest.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-generator.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-cache.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-output.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-customizer.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-theme-json-sync.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-options.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-breadcrumbs.php';

add_action('after_setup_theme', function(): void {
    $registry     = new Dynamo_Token_Registry();
    $fonts        = new Dynamo_Font_Manifest(DYNAMO_PATH . '/fonts/fonts.json');
    $cache        = new Dynamo_CSS_Cache();
    $generator    = new Dynamo_CSS_Generator($registry, $fonts);
    $output       = new Dynamo_CSS_Output($generator, $cache);
    $customizer   = new Dynamo_Customizer($registry, $cache, $generator, $fonts);
    $theme_json   = new Dynamo_Theme_JSON_Sync($registry);
    $options      = new Dynamo_Options();
    $output->init();
    $customizer->init();
    $theme_json->init();
    $options->init();
    add_action('admin_notices', [$fonts, 'render_admin_notice']);
});

add_action('after_setup_theme', function(): void {
    load_theme_textdomain('dynamo', DYNAMO_PATH . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    register_nav_menus([
        'primary' => __('Primary Menu', 'dynamo'),
    ]);
});

function dynamo_inject_menu_toggle(string $nav_menu, stdClass $args): string {
    if (($args->theme_location ?? '') !== 'primary') {
        return $nav_menu;
    }
    $button = '<button type="button" class="dynamo-menu-toggle" aria-controls="primary-menu" aria-expanded="false" aria-label="' . esc_attr__('Toggle menu', 'dynamo') . '">'
        . '<span class="screen-reader-text">' . esc_html__('Menu', 'dynamo') . '</span>'
        . '<svg class="dynamo-menu-toggle__icon dynamo-menu-toggle__icon--open" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        . '<svg class="dynamo-menu-toggle__icon dynamo-menu-toggle__icon--close" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        . '</button>';
    return (string) preg_replace('/(<ul\b)/', $button . '$1', $nav_menu, 1);
}
add_filter('wp_nav_menu', 'dynamo_inject_menu_toggle', 10, 2);

add_action('wp_enqueue_scripts', function(): void {
    wp_enqueue_script(
        'dynamo-primary-nav',
        DYNAMO_URL . 'assets/js/primary-nav.js',
        [],
        DYNAMO_VERSION,
        true
    );
});

add_action('widgets_init', function(): void {
    register_sidebar([
        'name'          => __('Primary Sidebar', 'dynamo'),
        'id'            => 'sidebar-1',
        'description'   => __('Appears when a sidebar layout is active.', 'dynamo'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ]);
});

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'dynamo-style', DYNAMO_URL . 'assets/css/style.css', [], DYNAMO_VERSION );
} );

function dynamo_bust_css_cache(): void {
    (new Dynamo_CSS_Cache())->bust();
}
