<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FakeCustomizeManager {
    public array $panels   = [];
    public array $sections = [];
    public array $settings = [];
    public array $controls = [];

    public function add_panel(string $id, array $args): void {
        $this->panels[$id] = $args;
    }

    public function add_section(string $id, array $args): void {
        $this->sections[$id] = $args;
    }

    public function add_setting(string $id, array $args): void {
        $this->settings[$id] = $args;
    }

    public function add_control(object $control): void {
        $this->controls[] = $control;
    }
}


class CustomizerTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_transients'] = [];
        // Reset bust tracker
        $GLOBALS['wp_transients']['dynamo_css_' . DYNAMO_VERSION] = 'cached';
    }

    private function makeCustomizer(): Dynamo_Customizer {
        return new Dynamo_Customizer(new Dynamo_Token_Registry());
    }

    public function test_init_registers_customize_register_hook(): void {
        $this->makeCustomizer()->init();
        $this->assertArrayHasKey('customize_register', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_customize_save_after_hook(): void {
        $this->makeCustomizer()->init();
        $this->assertArrayHasKey('customize_save_after', $GLOBALS['wp_filter']);
    }

    public function test_customize_save_after_busts_cache(): void {
        $this->makeCustomizer()->init();

        // Verify cache exists
        $cache = new Dynamo_CSS_Cache();
        $this->assertNotNull($cache->get());

        // Fire the hook
        apply_filters('customize_save_after', null);

        // Cache should be busted
        $this->assertNull($cache->get());
    }

    public function test_register_adds_dynamo_colours_panel(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $this->assertArrayHasKey('dynamo_colours', $manager->panels);
    }

    public function test_register_adds_primary_colour_setting_with_postmessage_transport(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $this->assertArrayHasKey('dynamo_colors_primary', $manager->settings);
        $this->assertSame('postMessage', $manager->settings['dynamo_colors_primary']['transport']);
    }

    public function test_register_setting_default_matches_token_registry(): void {
        $registry = new Dynamo_Token_Registry();
        $manager  = new FakeCustomizeManager();
        (new Dynamo_Customizer($registry))->register($manager);
        $this->assertSame(
            $registry->get('colors-primary'),
            $manager->settings['dynamo_colors_primary']['default']
        );
    }

    public function test_register_adds_colour_controls_for_all_seven_tokens(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $control_ids = array_map(fn($c) => $c->id, $manager->controls);
        $this->assertContains('dynamo_colors_primary', $control_ids);
        $this->assertContains('dynamo_colors_secondary', $control_ids);
        $this->assertContains('dynamo_colors_accent', $control_ids);
        $this->assertContains('dynamo_colors_background', $control_ids);
        $this->assertContains('dynamo_colors_text', $control_ids);
        $this->assertContains('dynamo_colors_link', $control_ids);
        $this->assertContains('dynamo_colors_section_alt', $control_ids);
    }

    public function test_register_adds_colours_section_linked_to_panel(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $this->assertArrayHasKey('dynamo_colours_section', $manager->sections);
        $this->assertSame('dynamo_colours', $manager->sections['dynamo_colours_section']['panel']);
    }
}
