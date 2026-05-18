<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ColoursModuleTest extends TestCase {

    private array $all_colour_tokens = [
        'colors-primary'     => '#3b82f6',
        'colors-secondary'   => '#6b7280',
        'colors-accent'      => '#f59e0b',
        'colors-background'  => '#ffffff',
        'colors-text'        => '#111827',
        'colors-link'        => '#2563eb',
        'colors-section-alt' => '#f3f4f6',
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

    public function test_all_seven_colour_tokens_have_defaults(): void {
        $registry = new Dynamo_Token_Registry();
        $defaults  = $registry->all();
        foreach (array_keys($this->all_colour_tokens) as $token) {
            $this->assertArrayHasKey($token, $defaults, "Missing default for {$token}");
        }
    }

    public function test_generate_contains_all_seven_colour_custom_properties(): void {
        $css = $this->makeGenerator()->generate();
        foreach (array_keys($this->all_colour_tokens) as $token) {
            $prop = '--dynamo-' . $token;
            $this->assertStringContainsString($prop, $css, "Missing {$prop} in generated CSS");
        }
    }

    public function test_generate_with_known_token_set_produces_expected_declarations(): void {
        $css = $this->makeGenerator($this->all_colour_tokens)->generate();

        $this->assertStringContainsString('--dynamo-colors-primary: #3b82f6;', $css);
        $this->assertStringContainsString('--dynamo-colors-secondary: #6b7280;', $css);
        $this->assertStringContainsString('--dynamo-colors-accent: #f59e0b;', $css);
        $this->assertStringContainsString('--dynamo-colors-background: #ffffff;', $css);
        $this->assertStringContainsString('--dynamo-colors-text: #111827;', $css);
        $this->assertStringContainsString('--dynamo-colors-link: #2563eb;', $css);
        $this->assertStringContainsString('--dynamo-colors-section-alt: #f3f4f6;', $css);
    }

    public function test_generated_css_contains_no_unclosed_braces(): void {
        $css    = $this->makeGenerator()->generate();
        $opens  = substr_count($css, '{');
        $closes = substr_count($css, '}');
        $this->assertSame($opens, $closes, 'Unclosed braces in generated CSS');
    }
}
