<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CSSGeneratorTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function fixtureManifest(): Dynamo_Font_Manifest {
        return new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
    }

    private function makeGenerator(): Dynamo_CSS_Generator {
        return new Dynamo_CSS_Generator(new Dynamo_Token_Registry(), $this->fixtureManifest());
    }

    public function test_generate_contains_colors_primary_custom_property(): void {
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-colors-primary', $css);
    }

    public function test_dynamo_css_colors_filter_can_append_extra_css(): void {
        add_filter('dynamo_css_colors', function(string $css): string {
            return $css . "\n  --extra: blue;";
        });
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--extra: blue;', $css);
        $this->assertStringContainsString('--dynamo-colors-primary', $css);
    }

    public function test_empty_module_list_returns_empty_string(): void {
        add_filter('dynamo_css_modules', fn() => []);
        $this->assertSame('', $this->makeGenerator()->generate());
    }

    public function test_face_bearing_slug_resolves_to_quoted_label_and_fallback(): void {
        add_filter('dynamo_token_defaults', function(array $defaults): array {
            $defaults['typography-body-font-family'] = 'inter';
            return $defaults;
        });
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-typography-body-font-family: "Inter", sans-serif;', $css);
    }

    public function test_fontless_slug_resolves_to_fallback_only(): void {
        add_filter('dynamo_token_defaults', function(array $defaults): array {
            $defaults['typography-body-font-family'] = 'system-sans';
            return $defaults;
        });
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString(
            "--dynamo-typography-body-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;",
            $css
        );
    }

    public function test_unknown_slug_resolves_to_hardcoded_system_fallback(): void {
        add_filter('dynamo_token_defaults', function(array $defaults): array {
            $defaults['typography-body-font-family'] = 'does-not-exist';
            return $defaults;
        });
        $css = $this->makeGenerator()->generate();
        $this->assertStringContainsString('--dynamo-typography-body-font-family:', $css);
        $this->assertStringNotContainsString('--dynamo-typography-body-font-family: does-not-exist', $css);
        $this->assertStringNotContainsString('--dynamo-typography-body-font-family: sans-serif;', $css);
        $this->assertStringContainsString('-apple-system', $css);
    }

    public function test_unknown_slug_triggers_doing_it_wrong_notice(): void {
        $GLOBALS['wp_doing_it_wrong'] = [];
        add_filter('dynamo_token_defaults', function(array $defaults): array {
            $defaults['typography-body-font-family'] = 'does-not-exist';
            return $defaults;
        });
        $this->makeGenerator()->generate();
        $calls = $GLOBALS['wp_doing_it_wrong'];
        $this->assertNotEmpty($calls);
        $combined = implode(' ', array_map(fn($c) => $c['message'], $calls));
        $this->assertStringContainsString('does-not-exist', $combined);
        $this->assertStringContainsString('typography-body-font-family', $combined);
    }
}
