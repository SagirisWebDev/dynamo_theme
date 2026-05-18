<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceShopLayoutTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_theme_mods'] = [];
    }

    private function makeGenerator(): Dynamo_CSS_Generator {
        return new Dynamo_CSS_Generator(new Dynamo_Token_Registry(), new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json'));
    }

    // --- Tokens / defaults ---

    public function test_registry_contains_shop_columns_default_three(): void {
        $registry = new Dynamo_Token_Registry();
        $this->assertSame('3', $registry->get('woocommerce-shop-columns'));
    }

    public function test_registry_contains_shop_products_per_page_default_twelve(): void {
        $registry = new Dynamo_Token_Registry();
        $this->assertSame('12', $registry->get('woocommerce-shop-products-per-page'));
    }

    public function test_generator_emits_shop_columns_custom_property(): void {
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-woocommerce-shop-columns: 3;', $css);
    }

    public function test_saved_theme_mod_overrides_shop_columns(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_shop_columns'] = '5';
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-woocommerce-shop-columns: 5;', $css);
    }

    // --- Customizer wiring ---

    public function test_customizer_registers_shop_layout_section_under_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_shop_layout', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_shop_layout']['panel']);
    }

    public function test_customizer_registers_shop_columns_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_shop_columns', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_shop_columns']['transport']);
        $this->assertSame('3', $manager->settings['dynamo_woocommerce_shop_columns']['default']);
    }

    public function test_customizer_registers_products_per_page_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_shop_products_per_page', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_shop_products_per_page']['transport']);
        $this->assertSame('12', $manager->settings['dynamo_woocommerce_shop_products_per_page']['default']);
    }

    public function test_customizer_registers_columns_control_constrained_one_to_six(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control_ids = array_map(fn($c) => $c->id, $manager->controls);
        $this->assertContains('dynamo_woocommerce_shop_columns', $control_ids);

        $control = $this->findControl($manager, 'dynamo_woocommerce_shop_columns');
        $this->assertNotNull($control, 'Expected columns control');
        $this->assertSame('number', $control->args['type'] ?? null);
        $this->assertSame(1, $control->args['input_attrs']['min'] ?? null);
        $this->assertSame(6, $control->args['input_attrs']['max'] ?? null);
    }

    public function test_customizer_registers_products_per_page_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control_ids = array_map(fn($c) => $c->id, $manager->controls);
        $this->assertContains('dynamo_woocommerce_shop_products_per_page', $control_ids);
    }

    // --- WooCommerce filters ---

    public function test_init_registers_loop_shop_columns_filter(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('loop_shop_columns', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_loop_shop_per_page_filter(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('loop_shop_per_page', $GLOBALS['wp_filter']);
    }

    public function test_loop_shop_columns_filter_returns_saved_value_as_int(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_shop_columns'] = '4';
        (new Dynamo_WooCommerce())->init();
        $value = apply_filters('loop_shop_columns', 4);
        $this->assertSame(4, $value);
    }

    public function test_loop_shop_columns_filter_falls_back_to_default_when_no_theme_mod(): void {
        (new Dynamo_WooCommerce())->init();
        $value = apply_filters('loop_shop_columns', 4);
        $this->assertSame(3, $value);
    }

    public function test_loop_shop_per_page_filter_returns_saved_value_as_int(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_shop_products_per_page'] = '24';
        (new Dynamo_WooCommerce())->init();
        $value = apply_filters('loop_shop_per_page', 12);
        $this->assertSame(24, $value);
    }

    public function test_loop_shop_per_page_filter_falls_back_to_default(): void {
        (new Dynamo_WooCommerce())->init();
        $value = apply_filters('loop_shop_per_page', 12);
        $this->assertSame(12, $value);
    }

    // --- Future-feature stub ---

    public function test_class_file_contains_future_style_switcher_stub(): void {
        $source = (string) file_get_contents(DYNAMO_PATH . 'includes/woocommerce/class-dynamo-woocommerce.php');
        $this->assertMatchesRegularExpression(
            '/future\s+feature.*style\s+switcher|style\s+switcher.*future\s+feature/is',
            $source,
            'Expected a future-feature labelled style switcher stub in the WC class source'
        );
    }

    // --- woocommerce.css uses the columns custom property ---

    public function test_woocommerce_css_uses_shop_columns_custom_property(): void {
        $css = (string) file_get_contents(DYNAMO_PATH . 'assets/css/woocommerce.css');
        $this->assertStringContainsString('var(--dynamo-woocommerce-shop-columns', $css);
        $this->assertStringContainsString('grid-template-columns', $css);
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
