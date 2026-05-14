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

    public function test_type_categories_for_text_returns_string(): void {
        $this->assertSame(['string'], Dynamo_CSS_Vocabulary::type_categories('text'));
    }

    public function test_type_categories_for_textarea_returns_string(): void {
        $this->assertSame(['string'], Dynamo_CSS_Vocabulary::type_categories('textarea'));
    }

    public function test_default_sanitizer_for_text_is_sanitize_text_field(): void {
        $this->assertSame('sanitize_text_field', Dynamo_CSS_Vocabulary::default_sanitizer('text'));
    }

    public function test_default_sanitizer_for_textarea_is_sanitize_textarea_field(): void {
        $this->assertSame('sanitize_textarea_field', Dynamo_CSS_Vocabulary::default_sanitizer('textarea'));
    }

    public function test_default_value_for_text_is_empty_string(): void {
        $this->assertSame('', Dynamo_CSS_Vocabulary::default_value('text'));
    }

    public function test_default_value_for_textarea_is_empty_string(): void {
        $this->assertSame('', Dynamo_CSS_Vocabulary::default_value('textarea'));
    }

    public function test_property_categories_for_font_family_includes_string(): void {
        $this->assertContains('string', Dynamo_CSS_Vocabulary::property_categories('font-family'));
    }

    public function test_property_categories_for_box_shadow_includes_any(): void {
        $this->assertContains('any', Dynamo_CSS_Vocabulary::property_categories('box-shadow'));
    }

    public function test_property_categories_for_content_includes_string(): void {
        $this->assertContains('string', Dynamo_CSS_Vocabulary::property_categories('content'));
    }

    public function test_property_categories_for_padding_includes_length(): void {
        $this->assertContains('length', Dynamo_CSS_Vocabulary::property_categories('padding'));
    }

    public function test_property_categories_for_padding_block_includes_length(): void {
        $this->assertContains('length', Dynamo_CSS_Vocabulary::property_categories('padding-block'));
    }

    public function test_property_categories_for_width_includes_length_and_keyword(): void {
        $categories = Dynamo_CSS_Vocabulary::property_categories('width');
        $this->assertContains('length', $categories);
        $this->assertContains('keyword', $categories);
    }

    public function test_property_categories_for_border_width_is_length_only(): void {
        $this->assertSame(['length'], Dynamo_CSS_Vocabulary::property_categories('border-width'));
    }

    public function test_property_categories_for_font_size_includes_length_and_number(): void {
        $categories = Dynamo_CSS_Vocabulary::property_categories('font-size');
        $this->assertContains('length', $categories);
        $this->assertContains('number', $categories);
    }

    public function test_property_categories_for_opacity_includes_number(): void {
        $this->assertSame(['number'], Dynamo_CSS_Vocabulary::property_categories('opacity'));
    }

    public function test_property_categories_for_z_index_includes_number(): void {
        $this->assertContains('number', Dynamo_CSS_Vocabulary::property_categories('z-index'));
    }

    public function test_dynamo_binding_categories_filter_extends_type_map(): void {
        add_filter('dynamo_binding_categories', function(array $map): array {
            $map['custom-type'] = ['keyword'];
            return $map;
        });
        $this->assertSame(['keyword'], Dynamo_CSS_Vocabulary::type_categories('custom-type'));
    }

    public function test_property_requirement_for_grid_template_columns_is_display_grid(): void {
        $this->assertSame(
            ['display' => 'grid'],
            Dynamo_CSS_Vocabulary::property_requirement('grid-template-columns')
        );
    }

    public function test_property_requirement_for_grid_template_rows_is_display_grid(): void {
        $this->assertSame(['display' => 'grid'], Dynamo_CSS_Vocabulary::property_requirement('grid-template-rows'));
    }

    public function test_property_requirement_for_grid_gap_props_is_display_grid(): void {
        $this->assertSame(['display' => 'grid'], Dynamo_CSS_Vocabulary::property_requirement('row-gap'));
        $this->assertSame(['display' => 'grid'], Dynamo_CSS_Vocabulary::property_requirement('column-gap'));
        $this->assertSame(['display' => 'grid'], Dynamo_CSS_Vocabulary::property_requirement('gap'));
    }

    public function test_property_requirement_for_flex_direction_is_display_flex(): void {
        $this->assertSame(['display' => 'flex'], Dynamo_CSS_Vocabulary::property_requirement('flex-direction'));
    }

    public function test_property_requirement_for_align_items_is_display_flex(): void {
        $this->assertSame(['display' => 'flex'], Dynamo_CSS_Vocabulary::property_requirement('align-items'));
    }

    public function test_property_requirement_for_top_is_position_relative(): void {
        $this->assertSame(['position' => 'relative'], Dynamo_CSS_Vocabulary::property_requirement('top'));
    }

    public function test_property_requirement_for_z_index_is_position_relative(): void {
        $this->assertSame(['position' => 'relative'], Dynamo_CSS_Vocabulary::property_requirement('z-index'));
    }

    public function test_property_requirement_for_property_without_prereq_returns_null(): void {
        $this->assertNull(Dynamo_CSS_Vocabulary::property_requirement('background-color'));
        $this->assertNull(Dynamo_CSS_Vocabulary::property_requirement('font-family'));
        $this->assertNull(Dynamo_CSS_Vocabulary::property_requirement('opacity'));
    }

    public function test_grid_column_is_parent_prereq_property(): void {
        $this->assertTrue(Dynamo_CSS_Vocabulary::has_parent_requirement('grid-column'));
        $this->assertTrue(Dynamo_CSS_Vocabulary::has_parent_requirement('grid-area'));
        $this->assertTrue(Dynamo_CSS_Vocabulary::has_parent_requirement('align-self'));
        $this->assertTrue(Dynamo_CSS_Vocabulary::has_parent_requirement('flex-grow'));
        $this->assertTrue(Dynamo_CSS_Vocabulary::has_parent_requirement('order'));
    }

    public function test_non_parent_prereq_property_is_not_flagged(): void {
        $this->assertFalse(Dynamo_CSS_Vocabulary::has_parent_requirement('grid-template-columns'));
        $this->assertFalse(Dynamo_CSS_Vocabulary::has_parent_requirement('background-color'));
        $this->assertFalse(Dynamo_CSS_Vocabulary::has_parent_requirement('top'));
    }

    public function test_dynamo_binding_requirements_filter_extends_map(): void {
        add_filter('dynamo_binding_requirements', function(array $map): array {
            $map['my-custom-prop'] = ['display' => 'grid'];
            return $map;
        });
        $this->assertSame(['display' => 'grid'], Dynamo_CSS_Vocabulary::property_requirement('my-custom-prop'));
    }
}
