<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceTest extends TestCase {

    private Dynamo_WooCommerce $woo;

    protected function setUp(): void {
        $GLOBALS['wp_filter']            = [];
        $GLOBALS['wp_theme_supports']    = [];
        $GLOBALS['wp_removed_actions']   = [];
        $GLOBALS['wp_enqueued_styles']   = [];
        $GLOBALS['wp_is_woocommerce']    = false;
        $GLOBALS['wp_is_cart']           = false;
        $GLOBALS['wp_is_checkout']       = false;
        $GLOBALS['wp_is_account_page']   = false;

        $this->woo = new Dynamo_WooCommerce();
    }

    public function test_register_theme_support_adds_woocommerce_support(): void {
        $this->woo->register_theme_support();
        $this->assertArrayHasKey('woocommerce', $GLOBALS['wp_theme_supports']);
    }

    public function test_replace_content_wrappers_removes_default_woocommerce_wrappers(): void {
        $this->woo->replace_content_wrappers();
        $this->assertContains('woocommerce_before_main_content', $GLOBALS['wp_removed_actions']);
        $this->assertContains('woocommerce_after_main_content', $GLOBALS['wp_removed_actions']);
    }

    public function test_replace_content_wrappers_registers_replacement_actions(): void {
        $this->woo->replace_content_wrappers();
        $this->assertArrayHasKey('woocommerce_before_main_content', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey('woocommerce_after_main_content', $GLOBALS['wp_filter']);
    }

    public function test_content_wrapper_open_outputs_dynamo_main_structure(): void {
        ob_start();
        $this->woo->output_content_wrapper_open();
        $output = ob_get_clean();

        $this->assertStringContainsString('<main', $output);
        $this->assertStringContainsString('id="main"', $output);
        $this->assertStringContainsString('class="site-main"', $output);
        $this->assertStringContainsString('dynamo-container', $output);
        $this->assertStringContainsString('dynamo-primary', $output);
    }

    public function test_open_and_close_wrappers_produce_balanced_markup(): void {
        ob_start();
        $this->woo->output_content_wrapper_open();
        $this->woo->output_content_wrapper_close();
        $markup = ob_get_clean();

        $this->assertStringContainsString('</main>', $markup);
        $this->assertSame(substr_count($markup, '<main'), substr_count($markup, '</main>'));
        $this->assertSame(substr_count($markup, '<div'), substr_count($markup, '</div>'));
    }

    public function test_enqueue_assets_enqueues_woocommerce_stylesheet_on_shop_pages(): void {
        $GLOBALS['wp_is_woocommerce'] = true;
        $this->woo->enqueue_assets();
        $this->assertContains('dynamo-woocommerce', $GLOBALS['wp_enqueued_styles']);
    }

    public function test_enqueue_assets_enqueues_on_cart(): void {
        $GLOBALS['wp_is_cart'] = true;
        $this->woo->enqueue_assets();
        $this->assertContains('dynamo-woocommerce', $GLOBALS['wp_enqueued_styles']);
    }

    public function test_enqueue_assets_enqueues_on_checkout(): void {
        $GLOBALS['wp_is_checkout'] = true;
        $this->woo->enqueue_assets();
        $this->assertContains('dynamo-woocommerce', $GLOBALS['wp_enqueued_styles']);
    }

    public function test_enqueue_assets_does_nothing_on_non_woocommerce_pages(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_header_cart_enabled'] = '0';
        $this->woo->enqueue_assets();
        $this->assertNotContains('dynamo-woocommerce', $GLOBALS['wp_enqueued_styles']);
    }

    public function test_init_registers_hooks_on_correct_actions(): void {
        $this->woo->init();
        $this->assertTrue(current_theme_supports('woocommerce'));
        $this->assertArrayHasKey('wp_enqueue_scripts', $GLOBALS['wp_filter']);
    }
}
