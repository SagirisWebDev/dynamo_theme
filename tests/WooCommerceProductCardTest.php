<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class WooCommerceProductCardTest extends TestCase {

    /**
     * Each removable element: setting suffix => spec on which WC action it lives.
     * Order corresponds to the visible order of the rendered card.
     */
    private array $removable_elements = [
        'image'        => ['tag' => 'woocommerce_before_shop_loop_item_title', 'callback' => 'woocommerce_template_loop_product_thumbnail', 'priority' => 10],
        'title'        => ['tag' => 'woocommerce_shop_loop_item_title',        'callback' => 'woocommerce_template_loop_product_title',     'priority' => 10],
        'rating'       => ['tag' => 'woocommerce_after_shop_loop_item_title',  'callback' => 'woocommerce_template_loop_rating',            'priority' => 5],
        'price'        => ['tag' => 'woocommerce_after_shop_loop_item_title',  'callback' => 'woocommerce_template_loop_price',             'priority' => 10],
        'add-to-cart'  => ['tag' => 'woocommerce_after_shop_loop_item',        'callback' => 'woocommerce_template_loop_add_to_cart',       'priority' => 10],
    ];

    protected function setUp(): void {
        $GLOBALS['wp_filter']               = [];
        $GLOBALS['wp_theme_mods']           = [];
        $GLOBALS['wp_removed_actions']      = [];
        $GLOBALS['wp_removed_action_specs'] = [];
    }

    // --- Tokens ---

    public function test_registry_contains_five_default_on_card_toggles(): void {
        $registry = new Dynamo_Token_Registry();
        foreach (['image', 'title', 'price', 'rating', 'add-to-cart'] as $element) {
            $key = 'woocommerce-card-show-' . $element;
            $this->assertSame('1', $registry->get($key), "{$key} default should be '1'");
        }
    }

    public function test_registry_short_description_defaults_off(): void {
        $this->assertSame('0', (new Dynamo_Token_Registry())->get('woocommerce-card-show-short-description'));
    }

    // --- Customizer ---

    public function test_customizer_registers_product_cards_section(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        $this->assertArrayHasKey('dynamo_woocommerce_product_cards', $manager->sections);
        $this->assertSame('dynamo_woocommerce', $manager->sections['dynamo_woocommerce_product_cards']['panel']);
    }

    public function test_customizer_registers_all_six_card_settings_with_postmessage(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        foreach (['image', 'title', 'price', 'rating', 'short-description', 'add-to-cart'] as $element) {
            $setting_id = 'dynamo_woocommerce_card_show_' . str_replace('-', '_', $element);
            $this->assertArrayHasKey($setting_id, $manager->settings, "Missing {$setting_id}");
            $this->assertSame('postMessage', $manager->settings[$setting_id]['transport']);
        }
    }

    public function test_customizer_registers_checkbox_controls_for_each_card_element(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_WooCommerce())->register_customizer($manager);
        foreach (['image', 'title', 'price', 'rating', 'short-description', 'add-to-cart'] as $element) {
            $setting_id = 'dynamo_woocommerce_card_show_' . str_replace('-', '_', $element);
            $control    = $this->findControl($manager, $setting_id);
            $this->assertNotNull($control);
            $this->assertSame('checkbox', $control->args['type'] ?? null);
        }
    }

    // --- Visibility application ---

    public function test_default_state_does_not_remove_any_card_callbacks(): void {
        (new Dynamo_WooCommerce())->apply_product_card_visibility();
        $card_tags = array_unique(array_column($this->removable_elements, 'tag'));
        $matches = array_filter(
            $GLOBALS['wp_removed_action_specs'] ?? [],
            fn($s) => in_array($s['tag'], $card_tags, true)
        );
        $this->assertEmpty($matches);
    }

    public function test_disabling_each_removable_element_removes_its_callback(): void {
        foreach ($this->removable_elements as $element => $spec) {
            $GLOBALS['wp_removed_action_specs'] = [];
            $GLOBALS['wp_theme_mods']           = [
                'dynamo_woocommerce_card_show_' . str_replace('-', '_', $element) => '0',
            ];
            (new Dynamo_WooCommerce())->apply_product_card_visibility();

            $hits = array_filter(
                $GLOBALS['wp_removed_action_specs'],
                fn($s) => $s['tag']      === $spec['tag']
                    && $s['callback'] === $spec['callback']
                    && $s['priority'] === $spec['priority']
            );
            $this->assertNotEmpty(
                $hits,
                "Disabling '{$element}' should remove {$spec['callback']} from {$spec['tag']}@{$spec['priority']}"
            );
        }
    }

    public function test_short_description_default_off_adds_no_renderer(): void {
        (new Dynamo_WooCommerce())->apply_product_card_visibility();
        $this->assertArrayNotHasKey(
            'woocommerce_after_shop_loop_item_title',
            array_filter($GLOBALS['wp_filter'], fn($k) => $k === 'woocommerce_after_shop_loop_item_title', ARRAY_FILTER_USE_KEY)
        );
    }

    public function test_enabling_short_description_registers_renderer_at_priority_15(): void {
        $GLOBALS['wp_theme_mods']['dynamo_woocommerce_card_show_short_description'] = '1';
        (new Dynamo_WooCommerce())->apply_product_card_visibility();
        $this->assertArrayHasKey('woocommerce_after_shop_loop_item_title', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey(15, $GLOBALS['wp_filter']['woocommerce_after_shop_loop_item_title']);
    }

    public function test_init_registers_template_redirect_for_card_visibility(): void {
        (new Dynamo_WooCommerce())->init();
        $this->assertArrayHasKey('template_redirect', $GLOBALS['wp_filter']);
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
