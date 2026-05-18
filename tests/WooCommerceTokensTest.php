<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceTokensTest extends TestCase {

    private array $woocommerce_tokens = [
        'woocommerce-sale-badge-bg',
        'woocommerce-sale-badge-color',
        'woocommerce-star-color',
    ];

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_theme_mods'] = [];
    }

    private function makeGenerator(): Dynamo_CSS_Generator {
        return new Dynamo_CSS_Generator(new Dynamo_Token_Registry(), new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json'));
    }

    // --- Token Registry ---

    public function test_registry_contains_all_three_woocommerce_tokens(): void {
        $defaults = (new Dynamo_Token_Registry())->all();
        foreach ($this->woocommerce_tokens as $token) {
            $this->assertArrayHasKey($token, $defaults, "Missing default for {$token}");
        }
    }

    public function test_woocommerce_token_defaults_are_non_empty_strings(): void {
        $registry = new Dynamo_Token_Registry();
        foreach ($this->woocommerce_tokens as $token) {
            $value = $registry->get($token);
            $this->assertIsString($value, "{$token} default must be a string");
            $this->assertNotSame('', $value, "{$token} default must not be empty");
        }
    }

    // --- CSS Generator: :root custom properties ---

    public function test_generator_emits_each_woocommerce_token_as_custom_property(): void {
        $css = $this->makeGenerator()->generate();
        foreach ($this->woocommerce_tokens as $token) {
            $this->assertStringContainsString("--dynamo-{$token}", $css, "Missing --dynamo-{$token}");
        }
    }

    public function test_generator_emits_woocommerce_selector_rules_referencing_tokens(): void {
        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString('.woocommerce', $css, 'Expected .woocommerce selector rules in output');
        $this->assertStringContainsString('var(--dynamo-woocommerce-sale-badge-bg)', $css);
        $this->assertStringContainsString('var(--dynamo-woocommerce-sale-badge-color)', $css);
        $this->assertStringContainsString('var(--dynamo-woocommerce-star-color)', $css);
    }

    public function test_add_to_cart_button_rule_maps_to_woocommerce_tokens(): void {
        $rules = $this->makeGenerator()->generate_woocommerce_rules();
        $this->assertMatchesRegularExpression(
            '/add_to_cart_button[^{]*\{[^}]*var\(--dynamo-woocommerce-add-to-cart-bg\)[^}]*var\(--dynamo-woocommerce-add-to-cart-color\)/s',
            $rules,
            'add-to-cart button rule must use the WooCommerce add-to-cart bg/text tokens'
        );
    }

    public function test_single_product_price_rule_maps_to_single_price_token(): void {
        $rules = $this->makeGenerator()->generate_woocommerce_rules();
        $this->assertMatchesRegularExpression(
            '/div\.product[^{]*\.price[^{]*\{[^}]*var\(--dynamo-woocommerce-single-price-color\)/s',
            $rules,
            'single product price rule must use var(--dynamo-woocommerce-single-price-color)'
        );
    }

    public function test_product_card_rule_maps_to_colors_background_token(): void {
        $rules = $this->makeGenerator()->generate_woocommerce_rules();
        $this->assertMatchesRegularExpression(
            '/li\.product[^{]*\{[^}]*var\(--dynamo-colors-background\)/s',
            $rules,
            'product card rule must use var(--dynamo-colors-background)'
        );
    }

    public function test_product_card_rule_uses_borders_and_shadow_tokens(): void {
        $rules = $this->makeGenerator()->generate_woocommerce_rules();
        $this->assertStringContainsString('var(--dynamo-borders-radius)', $rules);
        $this->assertStringContainsString('var(--dynamo-borders-color)', $rules);
        $this->assertStringContainsString('var(--dynamo-shadows-md)', $rules);
    }

    public function test_generator_woocommerce_rules_are_appended_after_root_block(): void {
        $css = $this->makeGenerator()->generate();
        $root_close = strrpos($css, '}');
        $first_woo  = strpos($css, '.woocommerce');
        $this->assertNotFalse($first_woo, 'Expected .woocommerce rules');
        // The :root block opens at the start; .woocommerce rules should appear after
        // the :root block opens (and after `:root { ... }` has emitted custom properties).
        $this->assertGreaterThan(strpos($css, ':root'), $first_woo);
    }

    public function test_saved_theme_mod_overrides_woocommerce_token_in_root(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_sale_badge_bg'] = '#ff00ff';
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-woocommerce-sale-badge-bg: #ff00ff;', $css);
    }

    // --- Customizer (registered via Dynamo_WooCommerce::register_customizer) ---

    public function test_woocommerce_customizer_registers_woocommerce_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce', $manager->panels);
    }

    public function test_woocommerce_customizer_registers_colours_section_under_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_colours', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_colours']['panel']);
    }

    public function test_woocommerce_customizer_registers_all_three_settings_with_postmessage(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        foreach ($this->woocommerce_tokens as $token) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $this->assertArrayHasKey($setting_id, $manager->settings, "Missing setting {$setting_id}");
            $this->assertSame('postMessage', $manager->settings[$setting_id]['transport']);
        }
    }

    public function test_woocommerce_customizer_registers_three_colour_controls(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $control_ids = array_map(fn($c) => $c->id, $manager->controls);
        foreach ($this->woocommerce_tokens as $token) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $this->assertContains($setting_id, $control_ids, "Missing control for {$setting_id}");
        }
    }

    public function test_woocommerce_init_registers_customize_register_hook(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('customize_register', $GLOBALS['wp_filter']);
    }

    public function test_woocommerce_setting_defaults_match_token_registry(): void {
        $registry = new Dynamo_Token_Registry();
        $manager  = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        foreach ($this->woocommerce_tokens as $token) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $this->assertSame(
                $registry->get($token),
                $manager->settings[$setting_id]['default'],
                "{$setting_id} default should match registry"
            );
        }
    }
}
