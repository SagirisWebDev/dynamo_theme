<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CSSVocabularyTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    public function test_type_categories_for_color_returns_color_category(): void {
        $this->assertSame(['color'], Dynamo_CSS_Vocabulary::type_categories('color'));
    }

    public function test_type_categories_for_unknown_returns_empty_array(): void {
        $this->assertSame([], Dynamo_CSS_Vocabulary::type_categories('does-not-exist'));
    }

    public function test_property_categories_for_background_color_contains_color(): void {
        $this->assertContains('color', Dynamo_CSS_Vocabulary::property_categories('background-color'));
    }

    public function test_property_categories_for_color_contains_color(): void {
        $this->assertContains('color', Dynamo_CSS_Vocabulary::property_categories('color'));
    }

    public function test_property_categories_for_unknown_returns_empty_array(): void {
        $this->assertSame([], Dynamo_CSS_Vocabulary::property_categories('not-a-css-property'));
    }

    public function test_is_unit_true_for_px(): void {
        $this->assertTrue(Dynamo_CSS_Vocabulary::is_unit('px'));
    }

    public function test_is_unit_false_for_pxx(): void {
        $this->assertFalse(Dynamo_CSS_Vocabulary::is_unit('pxx'));
    }

    public function test_default_sanitizer_for_color_is_sanitize_hex_color(): void {
        $this->assertSame('sanitize_hex_color', Dynamo_CSS_Vocabulary::default_sanitizer('color'));
    }

    public function test_default_value_for_color_is_black(): void {
        $this->assertSame('#000000', Dynamo_CSS_Vocabulary::default_value('color'));
    }

    public function test_control_class_for_color_is_wp_customize_color_control(): void {
        $this->assertSame('WP_Customize_Color_Control', Dynamo_CSS_Vocabulary::control_class('color'));
    }

    public function test_dynamo_binding_properties_filter_extends_property_whitelist(): void {
        add_filter('dynamo_binding_properties', function(array $props): array {
            $props['my-custom-prop'] = ['color'];
            return $props;
        });
        $this->assertContains('color', Dynamo_CSS_Vocabulary::property_categories('my-custom-prop'));
    }

    public function test_dynamo_binding_units_filter_extends_unit_whitelist(): void {
        add_filter('dynamo_binding_units', function(array $units): array {
            $units[] = 'svh';
            return $units;
        });
        $this->assertTrue(Dynamo_CSS_Vocabulary::is_unit('svh'));
    }

    public function test_dynamo_binding_categories_filter_extends_type_map(): void {
        add_filter('dynamo_binding_categories', function(array $map): array {
            $map['custom-type'] = ['keyword'];
            return $map;
        });
        $this->assertSame(['keyword'], Dynamo_CSS_Vocabulary::type_categories('custom-type'));
    }
}
