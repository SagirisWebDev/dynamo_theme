<?php
declare(strict_types=1);

class Dynamo_WooCommerce {

    private const COLOUR_TOKENS = [
        'woocommerce-sale-badge-bg'      => 'Sale Badge Background',
        'woocommerce-sale-badge-color'   => 'Sale Badge Text',
        'woocommerce-star-color'         => 'Star Rating',
        'woocommerce-add-to-cart-bg'     => 'Add to Cart Background',
        'woocommerce-add-to-cart-color'  => 'Add to Cart Text',
        'woocommerce-single-price-color' => 'Single Product Price',
        'woocommerce-loop-price-color'   => 'Shop Loop Price',
    ];

    private const HEADER_CART_POSITIONS = ['left', 'center', 'right'];

    private const SINGLE_PRODUCT_ELEMENTS = [
        'title'       => ['callback' => 'woocommerce_template_single_title',       'priority' => 5],
        'price'       => ['callback' => 'woocommerce_template_single_price',       'priority' => 10],
        'rating'      => ['callback' => 'woocommerce_template_single_rating',      'priority' => 10],
        'excerpt'     => ['callback' => 'woocommerce_template_single_excerpt',     'priority' => 20],
        'add-to-cart' => ['callback' => 'woocommerce_template_single_add_to_cart', 'priority' => 30],
        'meta'        => ['callback' => 'woocommerce_template_single_meta',        'priority' => 40],
    ];

    private const PRODUCT_CARD_REMOVABLE = [
        'image'       => ['tag' => 'woocommerce_before_shop_loop_item_title', 'callback' => 'woocommerce_template_loop_product_thumbnail', 'priority' => 10],
        'title'       => ['tag' => 'woocommerce_shop_loop_item_title',        'callback' => 'woocommerce_template_loop_product_title',     'priority' => 10],
        'rating'      => ['tag' => 'woocommerce_after_shop_loop_item_title',  'callback' => 'woocommerce_template_loop_rating',            'priority' => 5],
        'price'       => ['tag' => 'woocommerce_after_shop_loop_item_title',  'callback' => 'woocommerce_template_loop_price',             'priority' => 10],
        'add-to-cart' => ['tag' => 'woocommerce_after_shop_loop_item',        'callback' => 'woocommerce_template_loop_add_to_cart',       'priority' => 10],
    ];

    public function init(): void {
        $this->register_theme_support();
        $this->replace_content_wrappers();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('customize_register', [$this, 'register_customizer']);
        add_action('customize_controls_enqueue_scripts', [$this, 'enqueue_customizer_controls_assets']);
        add_action('dynamo_header_cart', [$this, 'render_header_cart_icon']);
        add_action('template_redirect', [$this, 'apply_single_product_visibility']);
        add_action('template_redirect', [$this, 'apply_cart_visibility']);
        add_action('template_redirect', [$this, 'apply_product_card_visibility']);
        add_action('template_redirect', [$this, 'apply_breadcrumb_visibility']);
        add_action('woocommerce_before_quantity_input_field', [$this, 'render_quantity_minus_button']);
        add_action('woocommerce_after_quantity_input_field', [$this, 'render_quantity_plus_button']);
        add_filter('gettext', [$this, 'filter_cart_button_text'], 10, 3);
        add_filter('loop_shop_columns', [$this, 'filter_loop_shop_columns'], PHP_INT_MAX);
        add_filter('loop_shop_per_page', [$this, 'filter_loop_shop_per_page'], PHP_INT_MAX);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'add_cart_count_fragment']);
        add_filter('woocommerce_output_related_products_args', [$this, 'filter_related_products_args']);
    }

    public function register_customizer(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_woocommerce', [
            'title'    => __('Dynamo: WooCommerce', 'dynamo'),
            'priority' => 35,
        ]);

        $this->register_colours_section($wp_customize);
        $this->register_shop_layout_section($wp_customize);
        $this->register_product_cards_section($wp_customize);
        $this->register_header_cart_section($wp_customize);
        $this->register_single_product_section($wp_customize);
        $this->register_quantity_buttons_section($wp_customize);
        $this->register_cart_checkout_section($wp_customize);
    }

    private function register_product_cards_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_product_cards', [
            'title' => __('Product Cards', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        $labels = [
            'image'             => __('Show product image', 'dynamo'),
            'title'             => __('Show title', 'dynamo'),
            'rating'            => __('Show star rating', 'dynamo'),
            'price'             => __('Show price', 'dynamo'),
            'short-description' => __('Show short description', 'dynamo'),
            'add-to-cart'       => __('Show add-to-cart button', 'dynamo'),
        ];

        foreach ($labels as $element => $label) {
            $token      = 'woocommerce-card-show-' . $element;
            $setting_id = 'dynamo_woocommerce_card_show_' . str_replace('-', '_', $element);
            $wp_customize->add_setting($setting_id, [
                'default'           => $registry->get($token) ?? '1',
                'sanitize_callback' => [$this, 'sanitize_boolish'],
                'transport'         => 'refresh',
            ]);
            $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                'label'   => $label,
                'section' => 'dynamo_woocommerce_product_cards',
                'type'    => 'checkbox',
            ]));
        }
    }

    public function apply_product_card_visibility(): void {
        foreach (self::PRODUCT_CARD_REMOVABLE as $element => $spec) {
            if (!$this->is_card_element_visible($element)) {
                remove_action($spec['tag'], $spec['callback'], $spec['priority']);
            }
        }
        if ($this->is_card_element_visible('short-description')) {
            add_action('woocommerce_after_shop_loop_item_title', [$this, 'render_card_short_description'], 15);
        }
    }

    public function render_card_short_description(): void {
        echo '<div class="dynamo-card-short-description">';
        if (function_exists('the_excerpt')) {
            the_excerpt();
        }
        echo '</div>';
    }

    private function is_card_element_visible(string $element): bool {
        $setting_id = 'dynamo_woocommerce_card_show_' . str_replace('-', '_', $element);
        $saved      = get_theme_mod($setting_id);
        if (false === $saved) {
            $saved = (new Dynamo_Token_Registry())->get('woocommerce-card-show-' . $element) ?? '1';
        }
        return '1' === (string) $saved;
    }

    private function register_cart_checkout_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_cart_checkout', [
            'title' => __('Cart & Checkout', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        $wp_customize->add_setting('dynamo_woocommerce_cart_button_text', [
            'default'           => $registry->get('woocommerce-cart-checkout-button-text') ?? '',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_cart_button_text', [
            'label'       => __('Checkout button text', 'dynamo'),
            'description' => __('Leave empty to keep the default WooCommerce label.', 'dynamo'),
            'section'     => 'dynamo_woocommerce_cart_checkout',
            'type'        => 'text',
        ]));

        $wp_customize->add_setting('dynamo_woocommerce_cross_sells_enabled', [
            'default'           => $registry->get('woocommerce-cart-cross-sells-enabled') ?? '1',
            'sanitize_callback' => [$this, 'sanitize_boolish'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_cross_sells_enabled', [
            'label'   => __('Show cross-sells ("You may also like") on the cart page', 'dynamo'),
            'section' => 'dynamo_woocommerce_cart_checkout',
            'type'    => 'checkbox',
        ]));
    }

    public function filter_cart_button_text(string $translated, string $original, string $domain): string {
        if ($domain !== 'woocommerce' || $original !== 'Proceed to checkout') {
            return $translated;
        }
        $custom = get_theme_mod('dynamo_woocommerce_cart_button_text');
        if (is_string($custom) && '' !== trim($custom)) {
            return $custom;
        }
        return $translated;
    }

    public function apply_cart_visibility(): void {
        if (!$this->is_cross_sells_enabled()) {
            remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
        }
    }

    public function apply_breadcrumb_visibility(): void {
        if (!Dynamo_Options::is_feature_enabled('breadcrumbs')) {
            remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        }
    }

    private function is_cross_sells_enabled(): bool {
        $saved = get_theme_mod('dynamo_woocommerce_cross_sells_enabled');
        if (false === $saved) {
            $saved = (new Dynamo_Token_Registry())->get('woocommerce-cart-cross-sells-enabled') ?? '1';
        }
        return '1' === (string) $saved;
    }

    private function register_quantity_buttons_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_quantity_buttons', [
            'title' => __('Quantity Buttons', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();
        $wp_customize->add_setting('dynamo_woocommerce_quantity_buttons_enabled', [
            'default'           => $registry->get('woocommerce-quantity-buttons-enabled') ?? '1',
            'sanitize_callback' => [$this, 'sanitize_boolish'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_quantity_buttons_enabled', [
            'label'   => __('Show +/- buttons on quantity inputs', 'dynamo'),
            'section' => 'dynamo_woocommerce_quantity_buttons',
            'type'    => 'checkbox',
        ]));
    }

    public function render_quantity_minus_button(): void {
        if (!$this->is_quantity_buttons_enabled()) {
            return;
        }
        echo '<button type="button" class="dynamo-quantity-minus" aria-label="' . esc_attr__('Decrease quantity', 'dynamo') . '">'
            . '<span aria-hidden="true">&minus;</span></button>';
    }

    public function render_quantity_plus_button(): void {
        if (!$this->is_quantity_buttons_enabled()) {
            return;
        }
        echo '<button type="button" class="dynamo-quantity-plus" aria-label="' . esc_attr__('Increase quantity', 'dynamo') . '">'
            . '<span aria-hidden="true">+</span></button>';
    }

    private function is_quantity_buttons_enabled(): bool {
        $saved = get_theme_mod('dynamo_woocommerce_quantity_buttons_enabled');
        if (false === $saved) {
            $saved = (new Dynamo_Token_Registry())->get('woocommerce-quantity-buttons-enabled') ?? '1';
        }
        return '1' === (string) $saved;
    }

    private function register_single_product_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_single_product', [
            'title' => __('Single Product', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        $labels = [
            'title'        => __('Show title', 'dynamo'),
            'price'        => __('Show price', 'dynamo'),
            'rating'       => __('Show star rating', 'dynamo'),
            'excerpt'      => __('Show short description', 'dynamo'),
            'add-to-cart'  => __('Show add-to-cart button', 'dynamo'),
            'meta'         => __('Show product meta (SKU, categories, tags)', 'dynamo'),
        ];

        foreach ($labels as $element => $label) {
            $token      = 'woocommerce-single-show-' . $element;
            $setting_id = 'dynamo_woocommerce_single_show_' . str_replace('-', '_', $element);
            $wp_customize->add_setting($setting_id, [
                'default'           => $registry->get($token) ?? '1',
                'sanitize_callback' => [$this, 'sanitize_boolish'],
                'transport'         => 'refresh',
            ]);
            $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                'label'   => $label,
                'section' => 'dynamo_woocommerce_single_product',
                'type'    => 'checkbox',
            ]));
        }

        $wp_customize->add_setting('dynamo_woocommerce_single_related_columns', [
            'default'           => $registry->get('woocommerce-single-related-columns') ?? '4',
            'sanitize_callback' => [$this, 'sanitize_related_columns'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_single_related_columns', [
            'label'       => __('Related products columns', 'dynamo'),
            'section'     => 'dynamo_woocommerce_single_product',
            'type'        => 'number',
            'input_attrs' => ['min' => 1, 'max' => 6, 'step' => 1],
        ]));
    }

    public function sanitize_related_columns(mixed $value): string {
        $int = (int) $value;
        if ($int < 1) {
            $int = 1;
        } elseif ($int > 6) {
            $int = 6;
        }
        return (string) $int;
    }

    public function apply_single_product_visibility(): void {
        foreach (self::SINGLE_PRODUCT_ELEMENTS as $element => $spec) {
            if (!$this->is_single_element_visible($element)) {
                remove_action('woocommerce_single_product_summary', $spec['callback'], $spec['priority']);
            }
        }
    }

    private function is_single_element_visible(string $element): bool {
        $setting_id = 'dynamo_woocommerce_single_show_' . str_replace('-', '_', $element);
        $saved      = get_theme_mod($setting_id);
        if (false === $saved) {
            $saved = (new Dynamo_Token_Registry())->get('woocommerce-single-show-' . $element) ?? '1';
        }
        return '1' === (string) $saved;
    }

    public function filter_related_products_args(array $args): array {
        $columns = $this->resolve_related_columns();
        $args['columns']        = $columns;
        $args['posts_per_page'] = $columns;
        return $args;
    }

    private function resolve_related_columns(): int {
        $saved = get_theme_mod('dynamo_woocommerce_single_related_columns');
        $value = (false !== $saved && '' !== $saved)
            ? (int) $saved
            : (int) ((new Dynamo_Token_Registry())->get('woocommerce-single-related-columns') ?? 4);
        if ($value < 1) {
            $value = 1;
        } elseif ($value > 6) {
            $value = 6;
        }
        return $value;
    }

    private function register_header_cart_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_header_cart', [
            'title' => __('Header Cart', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        $wp_customize->add_setting('dynamo_woocommerce_header_cart_enabled', [
            'default'           => $registry->get('woocommerce-header-cart-enabled') ?? '1',
            'sanitize_callback' => [$this, 'sanitize_boolish'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_header_cart_enabled', [
            'label'   => __('Show cart icon in header', 'dynamo'),
            'section' => 'dynamo_woocommerce_header_cart',
            'type'    => 'checkbox',
        ]));

        $wp_customize->add_setting('dynamo_woocommerce_header_cart_position', [
            'default'           => $registry->get('woocommerce-header-cart-position') ?? 'right',
            'sanitize_callback' => [$this, 'sanitize_header_cart_position'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_header_cart_position', [
            'label'   => __('Position', 'dynamo'),
            'section' => 'dynamo_woocommerce_header_cart',
            'type'    => 'select',
            'choices' => [
                'left'   => __('Left', 'dynamo'),
                'center' => __('Center', 'dynamo'),
                'right'  => __('Right', 'dynamo'),
            ],
        ]));
    }

    public function sanitize_boolish(mixed $value): string {
        return $value && '0' !== (string) $value ? '1' : '0';
    }

    public function sanitize_header_cart_position(mixed $value): string {
        $value = is_string($value) ? $value : '';
        return in_array($value, self::HEADER_CART_POSITIONS, true) ? $value : 'right';
    }

    public function render_header_cart_icon(): void {
        if (!$this->is_header_cart_enabled()) {
            return;
        }

        $position = $this->get_header_cart_position();
        $count    = $this->cart_count();
        $url      = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

        echo '<a class="dynamo-header-cart dynamo-header-cart--' . esc_attr($position) . '" href="' . esc_url($url) . '" aria-label="' . esc_attr__('View cart', 'dynamo') . '">'
            . '<svg class="dynamo-header-cart__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24">'
            . '<path d="M6 6h15l-1.5 9h-12L6 6Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>'
            . '<circle cx="9" cy="20" r="1.5" fill="currentColor"/>'
            . '<circle cx="18" cy="20" r="1.5" fill="currentColor"/>'
            . '<path d="M6 6L4 3H2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
            . '</svg>'
            . '<span class="dynamo-header-cart__count">' . esc_html((string) $count) . '</span>'
            . '</a>';
    }

    public function add_cart_count_fragment(array $fragments): array {
        $fragments['span.dynamo-header-cart__count'] =
            '<span class="dynamo-header-cart__count">' . esc_html((string) $this->cart_count()) . '</span>';
        return $fragments;
    }

    private function is_header_cart_enabled(): bool {
        $saved = get_theme_mod('dynamo_woocommerce_header_cart_enabled');
        if (false === $saved) {
            $saved = (new Dynamo_Token_Registry())->get('woocommerce-header-cart-enabled') ?? '1';
        }
        return '1' === (string) $saved;
    }

    private function get_header_cart_position(): string {
        $saved = get_theme_mod('dynamo_woocommerce_header_cart_position');
        $value = is_string($saved) && '' !== $saved
            ? $saved
            : ((new Dynamo_Token_Registry())->get('woocommerce-header-cart-position') ?? 'right');
        return in_array($value, self::HEADER_CART_POSITIONS, true) ? $value : 'right';
    }

    private function cart_count(): int {
        if (!function_exists('WC')) {
            return 0;
        }
        $wc = WC();
        if (!isset($wc->cart) || !is_object($wc->cart)) {
            return 0;
        }
        return (int) $wc->cart->get_cart_contents_count();
    }

    private function register_colours_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_colours', [
            'title' => __('WooCommerce Colours', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        foreach (self::COLOUR_TOKENS as $token => $label) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $wp_customize->add_setting($setting_id, [
                'default'           => $registry->get($token) ?? '#000000',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'postMessage',
            ]);
            $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $setting_id, [
                'label'   => __($label, 'dynamo'),
                'section' => 'dynamo_woocommerce_colours',
            ]));
        }
    }

    private function register_shop_layout_section(object $wp_customize): void {
        $wp_customize->add_section('dynamo_woocommerce_shop_layout', [
            'title' => __('Shop Layout', 'dynamo'),
            'panel' => 'dynamo_woocommerce',
        ]);

        $registry = new Dynamo_Token_Registry();

        $wp_customize->add_setting('dynamo_woocommerce_shop_columns', [
            'default'           => $registry->get('woocommerce-shop-columns') ?? '3',
            'sanitize_callback' => [$this, 'sanitize_shop_columns'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_shop_columns', [
            'label'       => __('Columns per row', 'dynamo'),
            'section'     => 'dynamo_woocommerce_shop_layout',
            'type'        => 'number',
            'input_attrs' => ['min' => 1, 'max' => 6, 'step' => 1],
        ]));

        $wp_customize->add_setting('dynamo_woocommerce_shop_products_per_page', [
            'default'           => $registry->get('woocommerce-shop-products-per-page') ?? '12',
            'sanitize_callback' => [$this, 'sanitize_products_per_page'],
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_shop_products_per_page', [
            'label'       => __('Products per page', 'dynamo'),
            'section'     => 'dynamo_woocommerce_shop_layout',
            'type'        => 'number',
            'input_attrs' => ['min' => 1, 'step' => 1],
        ]));

        // Future feature: Shop Style Switcher (grid / modern / list).
        // The grid layout is the only active style for v1.1.0; this stub will be
        // enabled when the modern card and list variants land. When unblocking,
        // also wire matching markup overrides in the WooCommerce template hooks.
        //
        // $wp_customize->add_setting('dynamo_woocommerce_shop_style', [
        //     'default'           => 'grid',
        //     'sanitize_callback' => 'sanitize_text_field',
        //     'transport'         => 'postMessage',
        // ]);
        // $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'dynamo_woocommerce_shop_style', [
        //     'label'   => __('Shop Style', 'dynamo'),
        //     'section' => 'dynamo_woocommerce_shop_layout',
        //     'type'    => 'select',
        //     'choices' => [
        //         'grid'   => __('Grid', 'dynamo'),
        //         'modern' => __('Modern Card', 'dynamo'),
        //         'list'   => __('List', 'dynamo'),
        //     ],
        // ]));
    }

    public function sanitize_shop_columns(mixed $value): string {
        $int = (int) $value;
        if ($int < 1) {
            $int = 1;
        } elseif ($int > 6) {
            $int = 6;
        }
        return (string) $int;
    }

    public function sanitize_products_per_page(mixed $value): string {
        $int = (int) $value;
        return (string) max(1, $int);
    }

    public function filter_loop_shop_columns(mixed $default): int {
        $registry = new Dynamo_Token_Registry();
        $saved    = get_theme_mod('dynamo_woocommerce_shop_columns');
        if (false !== $saved && '' !== $saved) {
            return (int) $saved;
        }
        return (int) ($registry->get('woocommerce-shop-columns') ?? $default);
    }

    public function filter_loop_shop_per_page(mixed $default): int {
        $registry = new Dynamo_Token_Registry();
        $saved    = get_theme_mod('dynamo_woocommerce_shop_products_per_page');
        if (false !== $saved && '' !== $saved) {
            return (int) $saved;
        }
        return (int) ($registry->get('woocommerce-shop-products-per-page') ?? $default);
    }

    public function register_theme_support(): void {
        add_theme_support('woocommerce');
    }

    public function replace_content_wrappers(): void {
        remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
        remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
        add_action('woocommerce_before_main_content', [$this, 'output_content_wrapper_open'], 10);
        add_action('woocommerce_after_main_content', [$this, 'output_content_wrapper_close'], 10);
    }

    public function output_content_wrapper_open(): void {
        echo '<main id="main" class="site-main">'
            . '<div class="dynamo-container dynamo-content-wrap">'
            . '<div class="dynamo-primary">';
    }

    public function output_content_wrapper_close(): void {
        echo '</div></div></main>';
    }

    public function enqueue_assets(): void {
        $header_cart_enabled = $this->is_header_cart_enabled();
        if ($header_cart_enabled) {
            wp_enqueue_script('wc-cart-fragments');
        }
        if (!$this->is_woocommerce_page() && !$header_cart_enabled) {
            return;
        }
        wp_enqueue_style(
            'dynamo-woocommerce',
            DYNAMO_URL . 'assets/css/woocommerce.css',
            [],
            DYNAMO_VERSION
        );
        if (!$this->is_woocommerce_page()) {
            return;
        }
        if ($this->is_quantity_buttons_enabled()) {
            wp_enqueue_script(
                'dynamo-woocommerce-quantity',
                DYNAMO_URL . 'assets/js/woocommerce-quantity.js',
                [],
                DYNAMO_VERSION,
                true
            );
        }
    }

    private function is_woocommerce_page(): bool {
        return (function_exists('is_woocommerce') && is_woocommerce())
            || (function_exists('is_cart') && is_cart())
            || (function_exists('is_checkout') && is_checkout())
            || (function_exists('is_account_page') && is_account_page());
    }

    public function enqueue_customizer_controls_assets(): void {
        wp_enqueue_script(
            'dynamo-woocommerce-customizer-controls',
            DYNAMO_URL . 'assets/js/woocommerce-customizer-controls.js',
            ['customize-controls'],
            DYNAMO_VERSION,
            true
        );
        wp_localize_script('dynamo-woocommerce-customizer-controls', 'dynamoWooCustomizer', [
            'sectionUrls' => $this->get_section_preview_urls(),
        ]);
    }

    private function get_section_preview_urls(): array {
        $urls = [];

        if (function_exists('wc_get_page_id')) {
            $shop_url = $this->get_wc_page_url('shop');
            if ('' !== $shop_url) {
                $urls['dynamo_woocommerce_shop_layout']  = $shop_url;
                $urls['dynamo_woocommerce_product_cards'] = $shop_url;
            }

            $checkout_url = $this->get_wc_page_url('checkout');
            if ('' !== $checkout_url) {
                $urls['dynamo_woocommerce_cart_checkout'] = $checkout_url;
            }
        }

        $product_url = $this->get_first_product_url();
        if ('' !== $product_url) {
            $urls['dynamo_woocommerce_single_product']    = $product_url;
            $urls['dynamo_woocommerce_quantity_buttons']  = $product_url;
        }

        return $urls;
    }

    private function get_wc_page_url(string $page): string {
        $page_id = wc_get_page_id($page);
        if ($page_id <= 0) {
            return '';
        }
        $url = get_permalink($page_id);
        return is_string($url) ? $url : '';
    }

    private function get_first_product_url(): string {
        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        if (empty($products)) {
            return '';
        }
        $url = get_permalink($products[0]);
        return is_string($url) ? $url : '';
    }
}
