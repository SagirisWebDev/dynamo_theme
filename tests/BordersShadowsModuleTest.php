<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BordersShadowsModuleTest extends TestCase {

    use MakesCustomizer;

    private array $border_tokens = [
        'borders-radius' => '0.375rem',
        'borders-color'  => '#e5e7eb',
        'borders-width'  => '1px',
    ];

    private array $shadow_tokens = [
        'shadows-sm-length'  => '0 1px 2px 0',
        'shadows-sm-color'   => '#000000',
        'shadows-sm-opacity' => '0.05',
        'shadows-md-length'  => '0 4px 6px -1px, 0 2px 4px -2px',
        'shadows-md-color'   => '#000000',
        'shadows-md-opacity' => '0.1',
    ];

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function allTokens(): array {
        return array_merge($this->border_tokens, $this->shadow_tokens);
    }

    private function makeGenerator(array $tokens = []): Dynamo_CSS_Generator {
        $registry = new Dynamo_Token_Registry();
        if (!empty($tokens)) {
            add_filter('dynamo_token_defaults', fn() => $tokens);
        }
        return new Dynamo_CSS_Generator($registry, new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json'));
    }

    public function test_all_border_and_shadow_tokens_have_defaults(): void {
        $registry = new Dynamo_Token_Registry();
        $defaults = $registry->all();
        foreach (array_keys($this->allTokens()) as $token) {
            $this->assertArrayHasKey($token, $defaults, "Missing default for {$token}");
        }
    }

    public function test_generate_contains_all_border_and_shadow_custom_properties(): void {
        $css = $this->makeGenerator()->generate();
        foreach (array_keys($this->border_tokens) as $token) {
            $prop = '--dynamo-' . $token;
            $this->assertStringContainsString($prop, $css, "Missing {$prop} in generated CSS");
        }
        foreach (['shadows-sm', 'shadows-md'] as $composed) {
            $prop = '--dynamo-' . $composed;
            $this->assertStringContainsString($prop, $css, "Missing composed {$prop} in generated CSS");
        }
    }

    public function test_generate_with_known_token_set_produces_expected_declarations(): void {
        $css = $this->makeGenerator($this->allTokens())->generate();
        $this->assertStringContainsString('--dynamo-borders-radius: 0.375rem;', $css);
        $this->assertStringContainsString('--dynamo-borders-color: #e5e7eb;', $css);
        $this->assertStringContainsString('--dynamo-borders-width: 1px;', $css);
        $this->assertStringContainsString('--dynamo-shadows-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);', $css);
        $this->assertStringContainsString('--dynamo-shadows-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);', $css);
    }

    public function test_generated_css_contains_no_unclosed_braces(): void {
        $css    = $this->makeGenerator()->generate();
        $opens  = substr_count($css, '{');
        $closes = substr_count($css, '}');
        $this->assertSame($opens, $closes, 'Unclosed braces in generated CSS');
    }

    public function test_customizer_register_adds_borders_shadows_panel(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_borders_shadows', $manager->panels);
    }

    public function test_customizer_register_adds_borders_shadows_section(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_borders_shadows_section', $manager->sections);
        $this->assertSame('dynamo_borders_shadows', $manager->sections['dynamo_borders_shadows_section']['panel']);
    }

    public function test_customizer_register_adds_all_border_and_shadow_controls(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains('dynamo_borders_radius', $control_ids);
        $this->assertContains('dynamo_borders_color', $control_ids);
        $this->assertContains('dynamo_borders_width', $control_ids);
        $this->assertContains('dynamo_shadows_sm_length', $control_ids);
        $this->assertContains('dynamo_shadows_sm_color', $control_ids);
        $this->assertContains('dynamo_shadows_sm_opacity', $control_ids);
        $this->assertContains('dynamo_shadows_md_length', $control_ids);
        $this->assertContains('dynamo_shadows_md_color', $control_ids);
        $this->assertContains('dynamo_shadows_md_opacity', $control_ids);
    }

    public function test_border_and_shadow_settings_use_postmessage_transport(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        foreach (array_keys($this->allTokens()) as $token) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $this->assertArrayHasKey($setting_id, $manager->settings, "Missing setting {$setting_id}");
            $this->assertSame('postMessage', $manager->settings[$setting_id]['transport'], "Wrong transport for {$setting_id}");
        }
    }
}
