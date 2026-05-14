<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BindingValidatorTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function validColorArgs(): array {
        return [
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ];
    }

    public function test_valid_color_args_produce_no_errors(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validColorArgs());
        $this->assertSame([], $errors);
    }

    public function test_missing_id_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['id']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('id', strtolower(implode(' ', $errors)));
    }

    public function test_missing_type_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['type']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('type', strtolower(implode(' ', $errors)));
    }

    public function test_missing_label_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['label']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('label', strtolower(implode(' ', $errors)));
    }

    public function test_missing_section_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['section']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('section', strtolower(implode(' ', $errors)));
    }

    public function test_missing_selector_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['selector']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('selector', strtolower(implode(' ', $errors)));
    }

    public function test_missing_property_is_reported(): void {
        $args = $this->validColorArgs();
        unset($args['property']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('property', strtolower(implode(' ', $errors)));
    }

    public function test_unknown_type_is_reported(): void {
        $args = $this->validColorArgs();
        $args['type'] = 'banana';
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('type', strtolower(implode(' ', $errors)));
    }

    public function test_unknown_property_is_reported(): void {
        $args = $this->validColorArgs();
        $args['property'] = 'not-a-real-property';
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('property', strtolower(implode(' ', $errors)));
    }

    public function test_incompatible_type_and_property_is_reported(): void {
        // 'color' type produces [color]; 'fill' accepts [color] so OK.
        // Pick a clearly incompatible pair via filter: extend properties with a length-only prop
        add_filter('dynamo_binding_properties', function(array $props): array {
            $props['my-length-only'] = ['length'];
            return $props;
        });
        $args = $this->validColorArgs();
        $args['property'] = 'my-length-only';
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    public function test_bad_unit_is_reported(): void {
        $args = $this->validColorArgs();
        $args['unit'] = 'pxx';
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('unit', strtolower(implode(' ', $errors)));
    }

    public function test_good_unit_is_accepted(): void {
        $args = $this->validColorArgs();
        $args['unit'] = 'rem';
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertSame([], $errors);
    }

    private function validTextArgs(array $overrides = []): array {
        return array_merge([
            'id'       => 'heading_font',
            'type'     => 'text',
            'label'    => 'Heading font',
            'section'  => 'typography',
            'selector' => 'h1, h2, h3',
            'property' => 'font-family',
        ], $overrides);
    }

    public function test_text_with_font_family_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validTextArgs());
        $this->assertSame([], $errors);
    }

    public function test_textarea_with_font_family_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validTextArgs(['type' => 'textarea']));
        $this->assertSame([], $errors);
    }

    public function test_text_with_color_property_is_incompatible(): void {
        // text → [string]; background-color → [color]; no intersection, no 'any' → reject.
        $errors = (new Dynamo_Binding_Validator())->validate($this->validTextArgs([
            'property' => 'background-color',
        ]));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    public function test_text_with_any_property_is_valid(): void {
        // text → [string]; box-shadow → [any]; 'any' is the open category, must pass.
        $errors = (new Dynamo_Binding_Validator())->validate($this->validTextArgs([
            'id'       => 'heading_shadow',
            'property' => 'box-shadow',
        ]));
        $this->assertSame([], $errors);
    }

    public function test_textarea_with_color_property_is_incompatible(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validTextArgs([
            'type'     => 'textarea',
            'property' => 'color',
        ]));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    private function validNumberArgs(array $overrides = []): array {
        return array_merge([
            'id'       => 'header_opacity',
            'type'     => 'number',
            'label'    => 'Header opacity',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'opacity',
        ], $overrides);
    }

    public function test_number_with_opacity_is_valid_without_unit(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs());
        $this->assertSame([], $errors);
    }

    public function test_range_with_opacity_is_valid_without_unit(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs(['type' => 'range']));
        $this->assertSame([], $errors);
    }

    public function test_number_with_padding_without_unit_is_incompatible(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs([
            'property' => 'padding-block',
        ]));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    public function test_number_with_padding_and_unit_is_valid(): void {
        // unit promotes type categories to include 'length'.
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs([
            'property' => 'padding-block',
            'unit'     => 'rem',
        ]));
        $this->assertSame([], $errors);
    }

    public function test_range_with_padding_and_unit_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs([
            'type'     => 'range',
            'property' => 'padding-block',
            'unit'     => 'rem',
        ]));
        $this->assertSame([], $errors);
    }

    public function test_number_with_color_property_is_incompatible_even_with_unit(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validNumberArgs([
            'property' => 'color',
            'unit'     => 'rem',
        ]));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    private function validRadioArgs(array $overrides = []): array {
        return array_merge([
            'id'       => 'sidebar_layout',
            'type'     => 'radio',
            'label'    => 'Sidebar layout',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
            'requires' => ['display' => 'grid'],
            'choices'  => [
                'left'  => ['label' => 'Left',  'value' => '300px 1fr'],
                'right' => ['label' => 'Right', 'value' => '1fr 300px'],
                'none'  => ['label' => 'None',  'value' => '1fr'],
            ],
        ], $overrides);
    }

    public function test_radio_with_three_column_choices_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs());
        $this->assertSame([], $errors);
    }

    public function test_select_with_three_column_choices_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs(['type' => 'select']));
        $this->assertSame([], $errors);
    }

    public function test_radio_without_choices_is_reported(): void {
        $args = $this->validRadioArgs();
        unset($args['choices']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('choices', strtolower(implode(' ', $errors)));
    }

    public function test_select_without_choices_is_reported(): void {
        $args = $this->validRadioArgs(['type' => 'select']);
        unset($args['choices']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertStringContainsString('choices', strtolower(implode(' ', $errors)));
    }

    public function test_radio_with_empty_choices_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs(['choices' => []]));
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('choices', strtolower(implode(' ', $errors)));
    }

    public function test_radio_with_flat_slug_to_label_choices_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'choices' => ['left' => 'Left', 'right' => 'Right'],
        ]));
        $this->assertNotEmpty($errors);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('choices', $msg);
    }

    public function test_radio_with_choice_missing_value_key_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'choices' => [
                'left'  => ['label' => 'Left'],
                'right' => ['label' => 'Right', 'value' => '1fr 300px'],
            ],
        ]));
        $this->assertStringContainsString('choices', strtolower(implode(' ', $errors)));
    }

    public function test_radio_with_choice_missing_label_key_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'choices' => [
                'left' => ['value' => '300px 1fr'],
            ],
        ]));
        $this->assertStringContainsString('choices', strtolower(implode(' ', $errors)));
    }

    public function test_radio_with_color_property_is_incompatible(): void {
        // radio → [keyword]; color → [color]; no intersection.
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'property' => 'color',
        ]));
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    public function test_radio_default_not_in_choices_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'default' => 'middle',
        ]));
        $this->assertNotEmpty($errors);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('default', $msg);
    }

    public function test_grid_template_columns_without_requires_is_reported(): void {
        // Bare radio binding against grid-template-columns omitting `requires`.
        $args = $this->validRadioArgs();
        unset($args['requires']);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertNotEmpty($errors);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('display: grid', $msg);
        $this->assertStringContainsString("'requires'", $msg);
    }

    public function test_grid_template_columns_with_correct_requires_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'requires' => ['display' => 'grid'],
        ]));
        $this->assertSame([], $errors);
    }

    public function test_grid_template_columns_with_mismatched_requires_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'requires' => ['display' => 'flex'],
        ]));
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('requires', $msg);
        $this->assertStringContainsString('grid', $msg);
    }

    public function test_grid_template_columns_with_requires_wrong_property_is_reported(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validRadioArgs([
            'requires' => ['position' => 'relative'],
        ]));
        $this->assertStringContainsString('display', strtolower(implode(' ', $errors)));
    }

    public function test_top_position_relative_requires_is_valid(): void {
        $args = $this->validTextArgs([
            'id'       => 'header_offset',
            'type'     => 'number',
            'selector' => '.site-header',
            'property' => 'top',
            'unit'     => 'px',
            'requires' => ['position' => 'relative'],
        ]);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertSame([], $errors);
    }

    public function test_top_without_requires_is_reported(): void {
        $args = $this->validTextArgs([
            'id'       => 'header_offset',
            'type'     => 'number',
            'selector' => '.site-header',
            'property' => 'top',
            'unit'     => 'px',
        ]);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('position: relative', $msg);
    }

    public function test_property_without_requirement_does_not_need_requires_field(): void {
        // background-color has no requirement — validator must not complain.
        $errors = (new Dynamo_Binding_Validator())->validate($this->validColorArgs());
        $this->assertSame([], $errors);
    }

    public function test_parent_requirement_property_grid_column_is_reported_as_unsupported(): void {
        $args = $this->validRadioArgs([
            'id'       => 'card_span',
            'selector' => '.card',
            'property' => 'grid-column',
            'choices'  => [
                'half' => ['label' => 'Half', 'value' => 'span 2'],
                'full' => ['label' => 'Full', 'value' => '1 / -1'],
            ],
        ]);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $this->assertNotEmpty($errors);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('parent', $msg);
        $this->assertStringContainsString('not handled automatically', $msg);
    }

    private function validUrlArgs(array $overrides = []): array {
        return array_merge([
            'id'       => 'logo_image',
            'type'     => 'image',
            'label'    => 'Logo image',
            'section'  => 'branding',
            'selector' => '.site-logo',
            'property' => 'background-image',
        ], $overrides);
    }

    public function test_url_type_with_background_image_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validUrlArgs(['type' => 'url']));
        $this->assertSame([], $errors);
    }

    public function test_image_type_with_background_image_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validUrlArgs());
        $this->assertSame([], $errors);
    }

    public function test_media_type_with_background_image_is_valid(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validUrlArgs(['type' => 'media']));
        $this->assertSame([], $errors);
    }

    public function test_url_type_with_color_property_is_incompatible(): void {
        $errors = (new Dynamo_Binding_Validator())->validate($this->validUrlArgs([
            'type'     => 'url',
            'property' => 'background-color',
        ]));
        $this->assertStringContainsString('incompatible', strtolower(implode(' ', $errors)));
    }

    public function test_parent_requirement_property_align_self_is_reported_as_unsupported(): void {
        $args = $this->validRadioArgs([
            'id'       => 'card_align',
            'selector' => '.card',
            'property' => 'align-self',
            'choices'  => [
                'start' => ['label' => 'Start', 'value' => 'start'],
                'end'   => ['label' => 'End',   'value' => 'end'],
            ],
        ]);
        $errors = (new Dynamo_Binding_Validator())->validate($args);
        $msg = strtolower(implode(' ', $errors));
        $this->assertStringContainsString('parent', $msg);
    }
}
