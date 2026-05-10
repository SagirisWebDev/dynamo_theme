<?php
declare(strict_types=1);

define('DYNAMO_VERSION', '1.0.0');
define('DYNAMO_PATH', get_template_directory());
define('DYNAMO_URL', trailingslashit(get_template_directory_uri()));

require_once DYNAMO_PATH . '/includes/class-dynamo-token-registry.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-generator.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-cache.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-css-output.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-customizer.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-theme-json-sync.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-options.php';
require_once DYNAMO_PATH . '/includes/class-dynamo-breadcrumbs.php';

add_action('after_setup_theme', function(): void {
    $registry     = new Dynamo_Token_Registry();
    $cache        = new Dynamo_CSS_Cache();
    $generator    = new Dynamo_CSS_Generator($registry);
    $output       = new Dynamo_CSS_Output($generator, $cache);
    $customizer   = new Dynamo_Customizer($registry, $cache, $generator);
    $theme_json   = new Dynamo_Theme_JSON_Sync($registry);
    $options      = new Dynamo_Options();
    $output->init();
    $customizer->init();
    $theme_json->init();
    $options->init();
});

add_action('after_setup_theme', function(): void {
    register_nav_menus([
        'primary' => __('Primary Menu', 'dynamo'),
    ]);
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

function dynamo_bust_css_cache(): void {
    (new Dynamo_CSS_Cache())->bust();
}
