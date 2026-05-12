<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CustomizerTest.php';

class BindingAdapterTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function colorRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ], $overrides));
        return $registry;
    }

    public function test_apply_creates_section_from_snake_case_slug(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->colorRegistry()))->apply($manager);
        $this->assertArrayHasKey('header_styling', $manager->sections);
        $this->assertSame('Header Styling', $manager->sections['header_styling']['title']);
    }

    public function test_apply_creates_setting_with_dynamo_prefixed_id_and_postmessage_transport(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->colorRegistry()))->apply($manager);
        $this->assertArrayHasKey('dynamo_header_bg', $manager->settings);
        $this->assertSame('postMessage', $manager->settings['dynamo_header_bg']['transport']);
    }

    public function test_apply_uses_color_control_class_for_color_type(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->colorRegistry()))->apply($manager);
        $this->assertCount(1, $manager->controls);
        $this->assertInstanceOf(WP_Customize_Color_Control::class, $manager->controls[0]);
        $this->assertSame('dynamo_header_bg', $manager->controls[0]->id);
    }

    public function test_apply_uses_default_sanitizer_when_none_supplied(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->colorRegistry()))->apply($manager);
        $this->assertSame('sanitize_hex_color', $manager->settings['dynamo_header_bg']['sanitize_callback']);
    }

    public function test_apply_respects_overridden_sanitizer(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->colorRegistry(['sanitize_callback' => 'my_custom_sanitizer'])
        ))->apply($manager);
        $this->assertSame('my_custom_sanitizer', $manager->settings['dynamo_header_bg']['sanitize_callback']);
    }

    public function test_apply_creates_section_only_once_for_repeated_slug(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header bg',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);
        $registry->register([
            'id'       => 'header_link',
            'type'     => 'color',
            'label'    => 'Header link',
            'section'  => 'header_styling',
            'selector' => '.site-header a',
            'property' => 'color',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertCount(1, $manager->sections);
    }

    public function test_apply_creates_panel_when_panel_supplied(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->colorRegistry(['panel' => 'site_styling'])
        ))->apply($manager);
        $this->assertArrayHasKey('site_styling', $manager->panels);
        $this->assertSame('Site Styling', $manager->panels['site_styling']['title']);
        $this->assertSame('site_styling', $manager->sections['header_styling']['panel']);
    }

    public function test_apply_uses_section_label_override(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->colorRegistry(['section_label' => 'Custom Label'])
        ))->apply($manager);
        $this->assertSame('Custom Label', $manager->sections['header_styling']['title']);
    }

    public function test_apply_uses_panel_label_override(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->colorRegistry([
                'panel'       => 'site_styling',
                'panel_label' => 'Site Styles',
            ])
        ))->apply($manager);
        $this->assertSame('Site Styles', $manager->panels['site_styling']['title']);
    }

    public function test_setting_default_matches_binding_default(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->colorRegistry(['default' => '#ff0000'])
        ))->apply($manager);
        $this->assertSame('#ff0000', $manager->settings['dynamo_header_bg']['default']);
    }

    private function textRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
            'id'       => 'heading_font',
            'type'     => 'text',
            'label'    => 'Heading font',
            'section'  => 'typography',
            'selector' => 'h1, h2, h3',
            'property' => 'font-family',
        ], $overrides));
        return $registry;
    }

    public function test_text_type_uses_generic_control_with_type_text(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->textRegistry()))->apply($manager);
        $this->assertCount(1, $manager->controls);
        $control = $manager->controls[0];
        $this->assertInstanceOf(WP_Customize_Control::class, $control);
        $this->assertNotInstanceOf(WP_Customize_Color_Control::class, $control);
        $this->assertSame('text', $control->args['type']);
    }

    public function test_text_type_uses_sanitize_text_field_by_default(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->textRegistry()))->apply($manager);
        $this->assertSame('sanitize_text_field', $manager->settings['dynamo_heading_font']['sanitize_callback']);
    }

    public function test_textarea_type_uses_generic_control_with_type_textarea(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->textRegistry(['type' => 'textarea', 'property' => 'content', 'selector' => '.banner::before'])
        ))->apply($manager);
        $control = $manager->controls[0];
        $this->assertInstanceOf(WP_Customize_Control::class, $control);
        $this->assertSame('textarea', $control->args['type']);
    }

    public function test_textarea_type_uses_sanitize_textarea_field_by_default(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(
            $this->textRegistry(['type' => 'textarea', 'property' => 'content', 'selector' => '.banner::before'])
        ))->apply($manager);
        $this->assertSame('sanitize_textarea_field', $manager->settings['dynamo_heading_font']['sanitize_callback']);
    }

    public function test_text_default_is_empty_string_when_omitted(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->textRegistry()))->apply($manager);
        $this->assertSame('', $manager->settings['dynamo_heading_font']['default']);
    }

    private function numberRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
            'id'       => 'header_pad',
            'type'     => 'range',
            'label'    => 'Header pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
            'unit'     => 'rem',
        ], $overrides));
        return $registry;
    }

    public function test_range_type_uses_generic_control_with_type_range(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->numberRegistry()))->apply($manager);
        $control = $manager->controls[0];
        $this->assertInstanceOf(WP_Customize_Control::class, $control);
        $this->assertSame('range', $control->args['type']);
    }

    public function test_number_type_uses_generic_control_with_type_number(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->numberRegistry([
            'type'     => 'number',
            'property' => 'opacity',
            'unit'     => null,
        ])))->apply($manager);
        // The 'unit' => null override is dropped by Registry::normalize because
        // it sets a default-via-key check; just verify type passes through.
        $this->assertSame('number', $manager->controls[0]->args['type']);
    }

    public function test_number_and_range_use_floatval_sanitizer_by_default(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->numberRegistry()))->apply($manager);
        $this->assertSame('floatval', $manager->settings['dynamo_header_pad']['sanitize_callback']);
    }

    public function test_number_default_is_zero_when_omitted(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->numberRegistry()))->apply($manager);
        $this->assertSame(0, $manager->settings['dynamo_header_pad']['default']);
    }

    public function test_input_attrs_pass_through_to_control_args(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->numberRegistry([
            'input_attrs' => ['min' => 0, 'max' => 6, 'step' => 0.25, 'placeholder' => 'rem'],
        ])))->apply($manager);
        $this->assertSame(
            ['min' => 0, 'max' => 6, 'step' => 0.25, 'placeholder' => 'rem'],
            $manager->controls[0]->args['input_attrs']
        );
    }
}
