<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceSingleProductTest extends TestCase {

    private array $element_specs = [
        'title'        => ['callback' => 'woocommerce_template_single_title',       'priority' => 5],
        'price'        => ['callback' => 'woocommerce_template_single_price',       'priority' => 10],
        'rating'       => ['callback' => 'woocommerce_template_single_rating',      'priority' => 10],
        'excerpt'      => ['callback' => 'woocommerce_template_single_excerpt',     'priority' => 20],
        'add-to-cart'  => ['callback' => 'woocommerce_template_single_add_to_cart', 'priority' => 30],
        'meta'         => ['callback' => 'woocommerce_template_single_meta',        'priority' => 40],
    ];

    protected function setUp(): void {
        $GLOBALS['wp_filter']               = [];
        $GLOBALS['wp_theme_mods']           = [];
        $GLOBALS['wp_removed_actions']      = [];
        $GLOBALS['wp_removed_action_specs'] = [];
    }

    // --- Tokens ---

    public function test_registry_contains_all_six_show_tokens_default_on(): void {
        $registry = new Dynamo_Token_Registry();
        foreach (array_keys($this->element_specs) as $element) {
            $key = 'woocommerce-single-show-' . $element;
            $this->assertSame('1', $registry->get($key), "{$key} default should be '1'");
        }
    }

    public function test_registry_contains_related_columns_default_four(): void {
        $this->assertSame('4', (new Dynamo_Token_Registry())->get('woocommerce-single-related-columns'));
    }

    // --- Customizer ---

    public function test_customizer_registers_single_product_section(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_single_product', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_single_product']['panel']);
    }

    public function test_customizer_registers_all_six_show_settings_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        foreach (array_keys($this->element_specs) as $element) {
            $setting_id = 'dynamo_woocommerce_single_show_' . str_replace('-', '_', $element);
            $this->assertArrayHasKey($setting_id, $manager->settings, "Missing setting {$setting_id}");
            $this->assertSame('refresh', $manager->settings[$setting_id]['transport']);
        }
    }

    public function test_customizer_registers_related_columns_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_single_related_columns', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_single_related_columns']['transport']);
        $this->assertSame('4', $manager->settings['dynamo_woocommerce_single_related_columns']['default']);
    }

    public function test_customizer_registers_checkbox_controls_for_each_element(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);

        foreach (array_keys($this->element_specs) as $element) {
            $setting_id = 'dynamo_woocommerce_single_show_' . str_replace('-', '_', $element);
            $control    = $this->findControl($manager, $setting_id);
            $this->assertNotNull($control, "Missing control for {$setting_id}");
            $this->assertSame('checkbox', $control->args['type'] ?? null, "Control {$setting_id} should be a checkbox");
        }
    }

    public function test_customizer_registers_related_columns_number_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control = $this->findControl($manager, 'dynamo_woocommerce_single_related_columns');
        $this->assertNotNull($control);
        $this->assertSame('number', $control->args['type'] ?? null);
    }

    // --- Hooks ---

    public function test_init_registers_template_redirect_for_single_visibility(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('template_redirect', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_related_products_args_filter(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('woocommerce_output_related_products_args', $GLOBALS['wp_filter']);
    }

    // --- apply_single_product_visibility ---

    public function test_default_state_does_not_remove_any_summary_callbacks(): void {
        (new Dynamo_WooCommerce())->apply_single_product_visibility();
        $matches = array_filter(
            $GLOBALS['wp_removed_action_specs'] ?? [],
            fn($s) => $s['tag'] === 'woocommerce_single_product_summary'
        );
        $this->assertEmpty($matches, 'No summary callbacks should be removed when all toggles are on');
    }

    public function test_disabling_each_element_removes_its_summary_callback(): void {
        foreach ($this->element_specs as $element => $spec) {
            $GLOBALS['wp_removed_action_specs'] = [];
            $GLOBALS['wp_theme_mods']           = [
                'dynamo_woocommerce_single_show_' . str_replace('-', '_', $element) => '0',
            ];
            (new Dynamo_WooCommerce())->apply_single_product_visibility();

            $hits = array_filter(
                $GLOBALS['wp_removed_action_specs'],
                fn($s) => $s['tag'] === 'woocommerce_single_product_summary'
                    && $s['callback'] === $spec['callback']
                    && $s['priority'] === $spec['priority']
            );
            $this->assertNotEmpty(
                $hits,
                "Disabling '{$element}' should remove {$spec['callback']} at priority {$spec['priority']}"
            );
        }
    }

    public function test_disabling_only_title_does_not_remove_other_callbacks(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_single_show_title'] = '0';
        (new Dynamo_WooCommerce())->apply_single_product_visibility();

        $other_removals = array_filter(
            $GLOBALS['wp_removed_action_specs'],
            fn($s) => $s['tag'] === 'woocommerce_single_product_summary'
                && $s['callback'] !== 'woocommerce_template_single_title'
        );
        $this->assertEmpty($other_removals);
    }

    // --- related_products_args filter ---

    public function test_related_products_args_returns_saved_column_count(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_single_related_columns'] = '6';
        (new Dynamo_WooCommerce())->init();
        $args = apply_filters('woocommerce_output_related_products_args', ['posts_per_page' => 3, 'columns' => 3]);
        $this->assertSame(6, $args['columns']);
        $this->assertSame(6, $args['posts_per_page']);
    }

    public function test_related_products_args_falls_back_to_default(): void {
        (new Dynamo_WooCommerce())->init();
        $args = apply_filters('woocommerce_output_related_products_args', ['posts_per_page' => 3, 'columns' => 3]);
        $this->assertSame(4, $args['columns']);
    }

    public function test_related_products_args_preserves_other_keys(): void {
        (new Dynamo_WooCommerce())->init();
        $args = apply_filters('woocommerce_output_related_products_args', ['orderby' => 'rand', 'columns' => 3]);
        $this->assertSame('rand', $args['orderby']);
    }

    private function findControl(FakeCustomizeManager $manager, string $id): ?object {
        foreach ($manager->controls as $control) {
            if ($control->id === $id) {
                return $control;
            }
        }
        return null;
    }
}
