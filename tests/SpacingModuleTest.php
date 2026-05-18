<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SpacingModuleTest extends TestCase {

    use MakesCustomizer;

    private array $all_spacing_tokens = [
        'spacing-header-padding-top'    => '2rem',
        'spacing-header-padding-bottom' => '2rem',
        'spacing-footer-padding-top'    => '2rem',
        'spacing-footer-padding-bottom' => '2rem',
        'spacing-content-padding-top'   => '2rem',
        'spacing-content-padding-bottom'=> '2rem',
        'spacing-content-padding-x'     => '1rem',
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

    public function test_all_spacing_tokens_have_defaults(): void {
        $registry = new Dynamo_Token_Registry();
        $defaults = $registry->all();
        foreach (array_keys($this->all_spacing_tokens) as $token) {
            $this->assertArrayHasKey($token, $defaults, "Missing default for {$token}");
        }
    }

    public function test_generate_contains_all_spacing_custom_properties(): void {
        $css = $this->makeGenerator()->generate();
        foreach (array_keys($this->all_spacing_tokens) as $token) {
            $prop = '--dynamo-' . $token;
            $this->assertStringContainsString($prop, $css, "Missing {$prop} in generated CSS");
        }
    }

    public function test_generate_with_known_spacing_tokens_produces_expected_declarations(): void {
        $css = $this->makeGenerator($this->all_spacing_tokens)->generate();
        $this->assertStringContainsString('--dynamo-spacing-header-padding-top: 2rem;', $css);
        $this->assertStringContainsString('--dynamo-spacing-content-padding-x: 1rem;', $css);
    }

    public function test_generate_with_empty_token_set_returns_empty_string(): void {
        add_filter('dynamo_token_defaults', fn() => []);
        $registry  = new Dynamo_Token_Registry();
        $generator = new Dynamo_CSS_Generator($registry, new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json'));
        $css = $generator->generate();
        $this->assertSame('', $css);
    }

    public function test_customizer_register_adds_spacing_panel(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_spacing', $manager->panels);
    }

    public function test_customizer_register_adds_header_footer_content_sections(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_spacing_header', $manager->sections);
        $this->assertArrayHasKey('dynamo_spacing_footer', $manager->sections);
        $this->assertArrayHasKey('dynamo_spacing_content', $manager->sections);
    }

    public function test_customizer_register_adds_all_spacing_controls(): void {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains('dynamo_spacing_header_padding_top', $control_ids);
        $this->assertContains('dynamo_spacing_header_padding_bottom', $control_ids);
        $this->assertContains('dynamo_spacing_footer_padding_top', $control_ids);
        $this->assertContains('dynamo_spacing_footer_padding_bottom', $control_ids);
        $this->assertContains('dynamo_spacing_content_padding_top', $control_ids);
        $this->assertContains('dynamo_spacing_content_padding_bottom', $control_ids);
        $this->assertContains('dynamo_spacing_content_padding_x', $control_ids);
    }
}
