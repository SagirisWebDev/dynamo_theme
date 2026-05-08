<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CSSGeneratorTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function makeGenerator(): Dynamo_CSS_Generator {
        return new Dynamo_CSS_Generator(new Dynamo_Token_Registry());
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
}
