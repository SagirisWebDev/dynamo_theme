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
}
