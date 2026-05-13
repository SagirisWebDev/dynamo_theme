<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceHeaderCartTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_theme_mods'] = [];
        $GLOBALS['wc_cart_count'] = 0;
        $GLOBALS['wc_cart_url']   = 'http://localhost/cart/';
    }

    // --- header.php ---

    public function test_header_php_contains_dynamo_header_cart_action(): void {
        $source = (string) file_get_contents(DYNAMO_PATH . 'header.php');
        $this->assertMatchesRegularExpression(
            "/do_action\(\s*['\"]dynamo_header_cart['\"]\s*\)/",
            $source,
            'header.php must contain do_action(\'dynamo_header_cart\')'
        );
    }

    // --- Tokens ---

    public function test_registry_contains_header_cart_enabled_default_on(): void {
        $this->assertSame('1', (new Dynamo_Token_Registry())->get('woocommerce-header-cart-enabled'));
    }

    public function test_registry_contains_header_cart_position_default_right(): void {
        $this->assertSame('right', (new Dynamo_Token_Registry())->get('woocommerce-header-cart-position'));
    }

    // --- Customizer ---

    public function test_customizer_registers_header_cart_section_under_panel(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_header_cart', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_header_cart']['panel']);
    }

    public function test_customizer_registers_enable_toggle_setting_with_refresh(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_header_cart_enabled', $manager->settings);
        $this->assertSame('refresh', $manager->settings['dynamo_woocommerce_header_cart_enabled']['transport']);
    }

    public function test_customizer_registers_position_select_with_three_choices(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_header_cart_position', $manager->settings);

        $control = $this->findControl($manager, 'dynamo_woocommerce_header_cart_position');
        $this->assertNotNull($control);
        $this->assertSame('select', $control->args['type'] ?? null);
        $this->assertEqualsCanonicalizing(
            ['left', 'center', 'right'],
            array_keys($control->args['choices'] ?? [])
        );
    }

    // --- Hooks wired in init ---

    public function test_init_registers_dynamo_header_cart_render_callback(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('dynamo_header_cart', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_woocommerce_add_to_cart_fragments_filter(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('woocommerce_add_to_cart_fragments', $GLOBALS['wp_filter']);
    }

    // --- render_header_cart_icon ---

    public function test_render_outputs_nothing_when_disabled(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_header_cart_enabled'] = '0';
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_outputs_anchor_when_enabled(): void {
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $output = ob_get_clean();
        $this->assertStringContainsString('<a ', $output);
        $this->assertStringContainsString('dynamo-header-cart', $output);
    }

    public function test_render_includes_position_modifier_class(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_header_cart_position'] = 'left';
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $output = ob_get_clean();
        $this->assertStringContainsString('dynamo-header-cart--left', $output);
    }

    public function test_render_position_falls_back_to_default_right(): void {
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $output = ob_get_clean();
        $this->assertStringContainsString('dynamo-header-cart--right', $output);
    }

    public function test_render_links_to_cart_url(): void {
        $GLOBALS['wc_cart_url'] = 'http://example.com/cart/';
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $output = ob_get_clean();
        $this->assertStringContainsString('href="http://example.com/cart/"', $output);
    }

    public function test_render_includes_count_span_with_current_count(): void {
        $GLOBALS['wc_cart_count'] = 7;
        ob_start();
        (new Dynamo_WooCommerce())->render_header_cart_icon();
        $output = ob_get_clean();
        $this->assertStringContainsString('dynamo-header-cart__count', $output);
        $this->assertMatchesRegularExpression(
            '/dynamo-header-cart__count[^>]*>\s*7\s*</',
            $output,
            'count span must contain the current cart count'
        );
    }

    // --- AJAX cart fragment ---

    public function test_add_cart_count_fragment_returns_array_keyed_by_count_selector(): void {
        $GLOBALS['wc_cart_count'] = 3;
        $fragments = (new Dynamo_WooCommerce())->add_cart_count_fragment([]);

        $this->assertIsArray($fragments);
        $matched_key = null;
        foreach (array_keys($fragments) as $selector) {
            if (str_contains($selector, 'dynamo-header-cart__count')) {
                $matched_key = $selector;
                break;
            }
        }
        $this->assertNotNull($matched_key, 'fragments must be keyed on a selector for the count span');
        $this->assertStringContainsString('>3<', $fragments[$matched_key]);
    }

    public function test_add_cart_count_fragment_preserves_existing_fragments(): void {
        $fragments = (new Dynamo_WooCommerce())->add_cart_count_fragment([
            'span.other' => '<span class="other">x</span>',
        ]);
        $this->assertArrayHasKey('span.other', $fragments);
    }

    // --- Styles ---

    public function test_woocommerce_css_contains_header_cart_base_rules(): void {
        $css = (string) file_get_contents(DYNAMO_PATH . 'assets/css/woocommerce.css');
        $this->assertStringContainsString('.dynamo-header-cart', $css);
        $this->assertStringContainsString('.dynamo-header-cart__icon', $css);
        $this->assertStringContainsString('.dynamo-header-cart__count', $css);
    }

    public function test_enqueue_assets_enqueues_wc_cart_fragments_on_wc_pages(): void {
        $GLOBALS['wp_is_woocommerce']  = true;
        $GLOBALS['wp_enqueued_scripts'] = [];
        (new Dynamo_WooCommerce())->enqueue_assets();
        $this->assertContains('wc-cart-fragments', $GLOBALS['wp_enqueued_scripts']);
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
