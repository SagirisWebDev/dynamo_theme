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

    public function test_radio_default_falls_back_to_first_choice_slug(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());
        $this->assertSame('left', $registry->all()['sidebar_layout']['default']);
    }

    public function test_select_default_falls_back_to_first_choice_slug(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs(['type' => 'select']));
        $this->assertSame('left', $registry->all()['sidebar_layout']['default']);
    }

    public function test_radio_explicit_default_is_preserved(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs(['default' => 'right']));
        $this->assertSame('right', $registry->all()['sidebar_layout']['default']);
    }

    public function test_radio_sanitize_callback_is_whitelist_closure(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());
        $sanitize = $registry->all()['sidebar_layout']['sanitize_callback'];
        $this->assertIsCallable($sanitize);
        $this->assertSame('left',  $sanitize('left'));
        $this->assertSame('right', $sanitize('right'));
        // Invalid slugs fall back to the default.
        $this->assertSame('left', $sanitize('not-a-slug'));
        $this->assertSame('left', $sanitize(''));
        // Must accept WP's two-arg call (value, WP_Customize_Setting) without throwing.
        $this->assertSame('right', $sanitize('right', new stdClass()));
    }

    public function test_select_sanitize_callback_is_whitelist_closure(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs(['type' => 'select', 'default' => 'none']));
        $sanitize = $registry->all()['sidebar_layout']['sanitize_callback'];
        $this->assertSame('none', $sanitize('rogue-slug'));
        $this->assertSame('left', $sanitize('left'));
    }

    public function test_explicit_sanitize_callback_overrides_radio_default(): void {
        $custom = static fn($v) => 'fixed';
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs(['sanitize_callback' => $custom]));
        $this->assertSame($custom, $registry->all()['sidebar_layout']['sanitize_callback']);
    }

    public function test_register_stores_requires_field_on_binding(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());
        $this->assertSame(
            ['display' => 'grid'],
            $registry->all()['sidebar_layout']['requires']
        );
    }

    public function test_two_bindings_same_selector_same_requires_both_register(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());
        $registry->register($this->validRadioArgs([
            'id'       => 'sidebar_rows',
            'property' => 'grid-template-rows',
            'choices'  => ['single' => ['label' => 'Single', 'value' => 'auto']],
        ]));
        $this->assertCount(2, $registry->all());
    }

    public function test_two_bindings_same_selector_conflicting_requires_throws(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/conflicting requires|display: grid|display: flex/i');
        $registry->register([
            'id'       => 'sidebar_flex',
            'type'     => 'radio',
            'label'    => 'Sidebar flex',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'flex-direction',
            'requires' => ['display' => 'flex'],
            'choices'  => [
                'row' => ['label' => 'Row', 'value' => 'row'],
                'col' => ['label' => 'Col', 'value' => 'column'],
            ],
        ]);
    }

    public function test_two_bindings_different_selectors_conflicting_requires_both_register(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register($this->validRadioArgs());
        $registry->register($this->validRadioArgs([
            'id'       => 'sidebar_flex',
            'selector' => '.other-content',
            'property' => 'flex-direction',
            'requires' => ['display' => 'flex'],
            'choices'  => [
                'row' => ['label' => 'Row', 'value' => 'row'],
                'col' => ['label' => 'Col', 'value' => 'column'],
            ],
        ]));
        $this->assertCount(2, $registry->all());
    }
}
