<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LayoutModuleTest extends TestCase {

    use MakesCustomizer;

    private array $layout_tokens = [
        'layout-container-max-width' => '1200px',
        'layout-content-width'       => '720px',
        'layout-sidebar-width'       => '300px',
    ];

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function makeGenerator(array $tokens = []): Dynamo_CSS_Generator {
        $registry = new Dynamo_Token_Registry();
        if (!empty($tokens)) {
            add_filter('dynamo_token_defaults', fn() => $tokens);
        }
        return new Dynamo_CSS_Generator($registry, new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json'));
    }

    public function test_all_layout_tokens_have_defaults(): void {
        $registry = new Dynamo_Token_Registry();
        $defaults = $registry->all();
        foreach (array_keys($this->layout_tokens) as $token) {
            $this->assertArrayHasKey($token, $defaults, "Missing default for {$token}");
        }
    }

    public function test_generate_contains_all_layout_custom_properties(): void {
        $css = $this->makeGenerator()->generate();
        foreach (array_keys($this->layout_tokens) as $token) {
            $prop = '--dynamo-' . $token;
            $this->assertStringContainsString($prop, $css, "Missing {$prop} in generated CSS");
        }
    }

    public function test_generate_with_known_layout_tokens_produces_expected_declarations(): void {
        $css = $this->makeGenerator($this->layout_tokens)->generate();
        $this->assertStringContainsString('--dynamo-layout-container-max-width: 1200px;', $css);
        $this->assertStringContainsString('--dynamo-layout-content-width: 720px;', $css);
        $this->assertStringContainsString('--dynamo-layout-sidebar-width: 300px;', $css);
    }

    public function test_customizer_register_adds_layout_panel(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_layout', $manager->panels);
    }

    public function test_customizer_register_adds_layout_section(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_layout_section', $manager->sections);
        $this->assertSame('dynamo_layout', $manager->sections['dynamo_layout_section']['panel']);
    }

    public function test_customizer_register_adds_all_layout_controls(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains('dynamo_layout_container_max_width', $control_ids);
        $this->assertContains('dynamo_layout_content_width', $control_ids);
        $this->assertContains('dynamo_layout_sidebar_width', $control_ids);
    }

    public function test_layout_settings_use_postmessage_transport(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertSame('postMessage', $manager->settings['dynamo_layout_container_max_width']['transport']);
        $this->assertSame('postMessage', $manager->settings['dynamo_layout_content_width']['transport']);
        $this->assertSame('postMessage', $manager->settings['dynamo_layout_sidebar_width']['transport']);
    }
}
