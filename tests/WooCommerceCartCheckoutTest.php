<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceCartCheckoutTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']               = [];
        $GLOBALS['wp_theme_mods']           = [];
        $GLOBALS['wp_removed_actions']      = [];
        $GLOBALS['wp_removed_action_specs'] = [];
    }

    // --- Tokens ---

    public function test_registry_contains_cart_button_text_default_empty(): void {
        $this->assertSame('', (new Dynamo_Token_Registry())->get('woocommerce-cart-checkout-button-text'));
    }

    public function test_registry_contains_cross_sells_enabled_default_on(): void {
        $this->assertSame('1', (new Dynamo_Token_Registry())->get('woocommerce-cart-cross-sells-enabled'));
    }

    // --- Customizer ---

    public function test_customizer_registers_cart_checkout_section_under_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_cart_checkout', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_cart_checkout']['panel']);
    }

    public function test_customizer_registers_button_text_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_cart_button_text', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_cart_button_text']['transport']);
    }

    public function test_customizer_registers_button_text_as_text_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control = $this->findControl($manager, 'dynamo_woocommerce_cart_button_text');
        $this->assertNotNull($control);
        $this->assertSame('text', $control->args['type'] ?? null);
    }

    public function test_customizer_registers_cross_sells_toggle_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_cross_sells_enabled', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_cross_sells_enabled']['transport']);

        $control = $this->findControl($manager, 'dynamo_woocommerce_cross_sells_enabled');
        $this->assertNotNull($control);
        $this->assertSame('checkbox', $control->args['type'] ?? null);
    }

    // --- Hooks wired in init ---

    public function test_init_registers_gettext_filter_for_button_text(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('gettext', $GLOBALS['wp_filter']);
    }

    // --- gettext filter behaviour ---

    public function test_gettext_filter_returns_custom_text_when_field_is_set(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_cart_button_text'] = 'Place your order';
        (new Dynamo_WooCommerce())->init();
        $result = apply_filters('gettext', 'Proceed to checkout', 'Proceed to checkout', 'woocommerce');
        $this->assertSame('Place your order', $result);
    }

    public function test_gettext_filter_falls_back_to_wc_default_when_field_is_empty(): void {
        (new Dynamo_WooCommerce())->init();
        $result = apply_filters('gettext', 'Proceed to checkout', 'Proceed to checkout', 'woocommerce');
        $this->assertSame('Proceed to checkout', $result);
    }

    public function test_gettext_filter_ignores_non_woocommerce_domain(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_cart_button_text'] = 'Place your order';
        (new Dynamo_WooCommerce())->init();
        $result = apply_filters('gettext', 'Proceed to checkout', 'Proceed to checkout', 'wp-core');
        $this->assertSame('Proceed to checkout', $result);
    }

    public function test_gettext_filter_ignores_unrelated_strings(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_cart_button_text'] = 'Place your order';
        (new Dynamo_WooCommerce())->init();
        $result = apply_filters('gettext', 'Add to cart', 'Add to cart', 'woocommerce');
        $this->assertSame('Add to cart', $result);
    }

    // --- Cross-sells removal ---

    public function test_disabling_cross_sells_removes_woocommerce_cross_sell_display(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_cross_sells_enabled'] = '0';
        (new Dynamo_WooCommerce())->apply_cart_visibility();

        $hits = array_filter(
            $GLOBALS['wp_removed_action_specs'],
            fn($s) => $s['tag'] === 'woocommerce_cart_collaterals'
                && $s['callback'] === 'woocommerce_cross_sell_display'
        );
        $this->assertNotEmpty($hits);
    }

    public function test_default_state_does_not_remove_cross_sell_display(): void {
        (new Dynamo_WooCommerce())->apply_cart_visibility();
        $hits = array_filter(
            $GLOBALS['wp_removed_action_specs'],
            fn($s) => $s['tag'] === 'woocommerce_cart_collaterals'
                && $s['callback'] === 'woocommerce_cross_sell_display'
        );
        $this->assertEmpty($hits);
    }

    public function test_init_registers_template_redirect_callback_for_cart_visibility(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('template_redirect', $GLOBALS['wp_filter']);
    }

    // --- CSS ---

    public function test_woocommerce_css_contains_block_checkout_selectors(): void {
        $css = (string) file_get_contents(DYNAMO_PATH . 'assets/css/woocommerce.css');
        $this->assertStringContainsString('.wp-block-woocommerce-checkout', $css);
        $this->assertStringContainsString('.wc-block-checkout', $css);
    }

    public function test_woocommerce_css_styles_cart_and_checkout_with_tokens(): void {
        $css = (string) file_get_contents(DYNAMO_PATH . 'assets/css/woocommerce.css');
        $this->assertStringContainsString('woocommerce-cart', $css);
        $this->assertStringContainsString('woocommerce-checkout', $css);
        $this->assertMatchesRegularExpression(
            '/woocommerce-(cart|checkout)[^{]*\{[^}]*var\(--dynamo-/s',
            $css,
            'cart/checkout selectors should reference a Dynamo token'
        );
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
