<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TokenRegistryTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    public function test_get_returns_default_for_colors_primary(): void {
        $registry = new Dynamo_Token_Registry();
        $this->assertNotNull($registry->get('colors-primary'));
    }

    public function test_get_returns_null_for_unknown_key(): void {
        $registry = new Dynamo_Token_Registry();
        $this->assertNull($registry->get('nonexistent-token'));
    }

    public function test_dynamo_token_defaults_filter_can_override_value(): void {
        add_filter('dynamo_token_defaults', function(array $defaults): array {
            $defaults['colors-primary'] = '#ff0000';
            return $defaults;
        });
        $registry = new Dynamo_Token_Registry();
        $this->assertSame('#ff0000', $registry->get('colors-primary'));
    }

    public function test_all_returns_array_containing_colors_primary(): void {
        $registry = new Dynamo_Token_Registry();
        $this->assertArrayHasKey('colors-primary', $registry->all());
    }

    public function test_typography_font_family_defaults_are_slug_references(): void {
        $registry = new Dynamo_Token_Registry();
        foreach (['body', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $element) {
            $this->assertSame(
                'system-sans',
                $registry->get("typography-{$element}-font-family"),
                "Expected typography-{$element}-font-family default to be the 'system-sans' slug"
            );
        }
    }
}
