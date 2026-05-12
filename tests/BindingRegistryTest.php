<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BindingRegistryTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function validColorArgs(array $overrides = []): array {
        return array_merge([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ], $overrides);
    }

    public function test_register_stores_binding_keyed_by_id(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validColorArgs());
        $all = $registry->all();
        $this->assertArrayHasKey('header_bg', $all);
    }

    public function test_register_returns_normalized_binding_with_default_applied(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validColorArgs());
        $binding = $registry->all()['header_bg'];
        $this->assertSame('#000000', $binding['default']);
    }

    public function test_register_preserves_explicit_default(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validColorArgs(['default' => '#ff0000']));
        $this->assertSame('#ff0000', $registry->all()['header_bg']['default']);
    }

    public function test_duplicate_id_throws(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validColorArgs());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/duplicate|already/i');
        $registry->register($this->validColorArgs());
    }

    public function test_invalid_args_throw(): void {
        $registry = new Dynamo_Binding_Registry();
        $this->expectException(InvalidArgumentException::class);
        $registry->register(['id' => 'broken']);
    }

    public function test_setting_id_is_prefixed_with_dynamo(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validColorArgs());
        $this->assertSame('dynamo_header_bg', $registry->all()['header_bg']['setting_id']);
    }

    public function test_all_returns_empty_array_initially(): void {
        $this->assertSame([], (new Dynamo_Binding_Registry())->all());
    }
}
