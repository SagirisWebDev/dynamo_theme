<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CustomizerTest extends TestCase {

    use MakesCustomizer;

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_transients'] = [];
        // Seed via the cache class so the transient key matches whatever scheme
        // the cache uses internally (currently includes style.css mtime).
        (new Dynamo_CSS_Cache())->set('cached');
    }

    private function fixtureManifest(): Dynamo_Font_Manifest {
        return new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
    }

    private function makeCustomizer(): Dynamo_Customizer {
        $registry  = new Dynamo_Token_Registry();
        $fonts     = $this->fixtureManifest();
        $generator = new Dynamo_CSS_Generator($registry, $fonts);
        $cache     = new Dynamo_CSS_Cache();
        return new Dynamo_Customizer($registry, $cache, $generator, $fonts);
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
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $registry = new Dynamo_Token_Registry();
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

    public function test_typography_font_family_control_is_a_select(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $control = $this->findControl($manager, 'dynamo_typography_body_font_family');
        $this->assertNotNull($control, 'Expected body font-family control to be registered');
        $this->assertSame('select', $control->args['type']);
    }

    public function test_typography_font_family_control_choices_come_from_manifest(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $control = $this->findControl($manager, 'dynamo_typography_body_font_family');
        $this->assertArrayHasKey('system-sans', $control->args['choices']);
        $this->assertArrayHasKey('inter', $control->args['choices']);
        $this->assertSame('System Sans', $control->args['choices']['system-sans']);
    }

    public function test_typography_font_family_sanitize_callback_rejects_unknown_slugs(): void {
        $manager = new FakeCustomizeManager();
        $this->makeCustomizer()->register($manager);
        $sanitize = $manager->settings['dynamo_typography_body_font_family']['sanitize_callback'];
        $this->assertSame('inter', $sanitize('inter'));
        $this->assertSame('system-sans', $sanitize('does-not-exist'));
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
