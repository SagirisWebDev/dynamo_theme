<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceQuantityButtonsTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_theme_mods']       = [];
        $GLOBALS['wp_enqueued_scripts'] = [];
        $GLOBALS['wp_enqueued_styles']  = [];
        $GLOBALS['wp_is_woocommerce']   = false;
        $GLOBALS['wp_is_cart']          = false;
        $GLOBALS['wp_is_checkout']      = false;
        $GLOBALS['wp_is_account_page']  = false;
    }

    // --- Token ---

    public function test_registry_contains_quantity_buttons_enabled_default_on(): void {
        $this->assertSame('1', (new Dynamo_Token_Registry())->get('woocommerce-quantity-buttons-enabled'));
    }

    // --- Customizer ---

    public function test_customizer_registers_quantity_buttons_section_under_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_quantity_buttons', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_quantity_buttons']['panel']);
    }

    public function test_customizer_registers_toggle_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_quantity_buttons_enabled', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_quantity_buttons_enabled']['transport']);
    }

    public function test_customizer_registers_toggle_as_checkbox(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control = $this->findControl($manager, 'dynamo_woocommerce_quantity_buttons_enabled');
        $this->assertNotNull($control);
        $this->assertSame('checkbox', $control->args['type'] ?? null);
    }

    // --- Hook registration ---

    public function test_init_registers_before_and_after_quantity_input_actions(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('woocommerce_before_quantity_input_field', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey('woocommerce_after_quantity_input_field', $GLOBALS['wp_filter']);
    }

    // --- Renderers ---

    public function test_render_minus_outputs_nothing_when_disabled(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_quantity_buttons_enabled'] = '0';
        ob_start();
        (new Dynamo_WooCommerce())->render_quantity_minus_button();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_plus_outputs_nothing_when_disabled(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_quantity_buttons_enabled'] = '0';
        ob_start();
        (new Dynamo_WooCommerce())->render_quantity_plus_button();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_minus_outputs_button_with_minus_class_when_enabled(): void {
        ob_start();
        (new Dynamo_WooCommerce())->render_quantity_minus_button();
        $output = ob_get_clean();
        $this->assertStringContainsString('<button', $output);
        $this->assertStringContainsString('dynamo-quantity-minus', $output);
        $this->assertStringContainsString('type="button"', $output);
    }

    public function test_render_plus_outputs_button_with_plus_class_when_enabled(): void {
        ob_start();
        (new Dynamo_WooCommerce())->render_quantity_plus_button();
        $output = ob_get_clean();
        $this->assertStringContainsString('<button', $output);
        $this->assertStringContainsString('dynamo-quantity-plus', $output);
        $this->assertStringContainsString('type="button"', $output);
    }

    // --- Asset enqueueing ---

    public function test_enqueue_assets_enqueues_quantity_js_on_wc_pages_when_enabled(): void {
        $GLOBALS['wp_is_woocommerce'] = true;
        (new Dynamo_WooCommerce())->enqueue_assets();
        $this->assertContains('dynamo-woocommerce-quantity', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_assets_does_not_enqueue_quantity_js_when_disabled(): void {
        $GLOBALS['wp_is_woocommerce'] = true;
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_quantity_buttons_enabled'] = '0';
        (new Dynamo_WooCommerce())->enqueue_assets();
        $this->assertNotContains('dynamo-woocommerce-quantity', $GLOBALS['wp_enqueued_scripts']);
    }

    // --- JS + CSS file presence ---

    public function test_woocommerce_js_file_exists_and_handles_clicks(): void {
        $js_path = DYNAMO_PATH . 'assets/js/woocommerce-quantity.js';
        $this->assertFileExists($js_path);
        $js = (string) file_get_contents($js_path);
        $this->assertStringContainsString('dynamo-quantity-minus', $js);
        $this->assertStringContainsString('dynamo-quantity-plus', $js);
        $this->assertStringContainsString("dispatchEvent", $js, 'JS should dispatch a change event');
    }

    public function test_woocommerce_css_contains_quantity_button_styles(): void {
        $css = (string) file_get_contents(DYNAMO_PATH . 'assets/css/woocommerce.css');
        $this->assertStringContainsString('.dynamo-quantity-minus', $css);
        $this->assertStringContainsString('.dynamo-quantity-plus', $css);
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
