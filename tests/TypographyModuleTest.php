<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TypographyModuleTest extends TestCase {

    use MakesCustomizer;

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function fixtureManifest(): Dynamo_Font_Manifest {
        return new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
    }

    private function makeGenerator(array $tokens = []): Dynamo_CSS_Generator {
        $registry = new Dynamo_Token_Registry();
        if (!empty($tokens)) {
            add_filter('dynamo_token_defaults', fn() => $tokens);
        }
        return new Dynamo_CSS_Generator($registry, $this->fixtureManifest());
    }

    public function test_generate_contains_typography_body_font_family(): void {
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-typography-body-font-family', $css);
    }

    public function test_generate_contains_typography_h1_font_size(): void {
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-typography-h1-font-size', $css);
    }

    public function test_generate_with_known_typography_tokens_produces_expected_declarations(): void {
        $tokens = [
            'typography-body-font-family' => 'inter',
            'typography-h1-font-size'     => '3rem',
        ];
        add_filter('dynamo_token_defaults', fn() => $tokens);
        $registry  = new Dynamo_Token_Registry();
        $generator = new Dynamo_CSS_Generator($registry, $this->fixtureManifest());
        $css = $generator->generate();
        $this->assertStringContainsString('--dynamo-typography-body-font-family: "Inter", sans-serif;', $css);
        $this->assertStringContainsString('--dynamo-typography-h1-font-size: 3rem;', $css);
    }

    public function test_generate_with_empty_token_set_returns_empty_string(): void {
        add_filter('dynamo_token_defaults', fn() => []);
        $registry  = new Dynamo_Token_Registry();
        $generator = new Dynamo_CSS_Generator($registry, $this->fixtureManifest());
        $css = $generator->generate();
        $this->assertSame('', $css);
    }

    public function test_customizer_register_adds_typography_panel_and_controls(): void {
        $registry   = new Dynamo_Token_Registry();
        $fonts      = $this->fixtureManifest();
        $generator  = new Dynamo_CSS_Generator($registry, $fonts);
        $manager    = new FakeCustomizeManager();
        $customizer = new Dynamo_Customizer($registry, new Dynamo_CSS_Cache(), $generator, $fonts);
        $customizer->register($manager);

        $this->assertArrayHasKey('dynamo_typography', $manager->panels);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);
        $this->assertContains('dynamo_typography_body_font_family', $control_ids);
        $this->assertContains('dynamo_typography_h1_font_size', $control_ids);
        $this->assertContains('dynamo_typography_h6_line_height', $control_ids);
    }

    public function test_all_typography_tokens_have_defaults(): void {
        $registry = new Dynamo_Token_Registry();
        $defaults  = $registry->all();
        $elements  = ['body', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $properties = ['font-family', 'font-size', 'font-weight', 'line-height'];
        foreach ($elements as $el) {
            foreach ($properties as $prop) {
                $key = "typography-{$el}-{$prop}";
                $this->assertArrayHasKey($key, $defaults, "Missing default for {$key}");
            }
        }
    }
}
