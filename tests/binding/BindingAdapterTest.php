<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../customizer/CustomizerTest.php';

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
        $sanitizer = $manager->settings['dynamo_header_pad']['sanitize_callback'];
        $this->assertIsCallable($sanitizer);
        $this->assertSame(1.5, $sanitizer('1.5'));
        // Must accept the second arg WP passes (the WP_Customize_Setting) without throwing.
        $this->assertSame(2.0, $sanitizer('2', new stdClass()));
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

    private function radioRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
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
        ], $overrides));
        return $registry;
    }

    public function test_radio_binding_creates_control_with_type_radio(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->radioRegistry()))->apply($manager);
        $this->assertSame('radio', $manager->controls[0]->args['type']);
    }

    public function test_radio_binding_flattens_choices_to_slug_to_label_for_wp_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->radioRegistry()))->apply($manager);
        $this->assertSame(
            ['left' => 'Left', 'right' => 'Right', 'none' => 'None'],
            $manager->controls[0]->args['choices']
        );
    }

    public function test_select_binding_creates_control_with_type_select_and_flattened_choices(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->radioRegistry(['type' => 'select'])))->apply($manager);
        $this->assertSame('select', $manager->controls[0]->args['type']);
        $this->assertSame(
            ['left' => 'Left', 'right' => 'Right', 'none' => 'None'],
            $manager->controls[0]->args['choices']
        );
    }

    public function test_radio_setting_default_is_first_slug_when_omitted(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->radioRegistry()))->apply($manager);
        $this->assertSame('left', $manager->settings['dynamo_sidebar_layout']['default']);
    }

    private function imageRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
            'id'       => 'logo_image',
            'type'     => 'image',
            'label'    => 'Logo image',
            'section'  => 'branding',
            'selector' => '.site-logo',
            'property' => 'background-image',
        ], $overrides));
        return $registry;
    }

    public function test_url_binding_creates_generic_control_with_type_url(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'url'])))->apply($manager);
        $this->assertInstanceOf(WP_Customize_Control::class, $manager->controls[0]);
        $this->assertSame('url', $manager->controls[0]->args['type']);
    }

    public function test_url_binding_default_sanitizer_is_esc_url_raw(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'url'])))->apply($manager);
        $this->assertSame('esc_url_raw', $manager->settings['dynamo_logo_image']['sanitize_callback']);
    }

    public function test_image_binding_uses_image_control_class(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry()))->apply($manager);
        $this->assertInstanceOf(WP_Customize_Image_Control::class, $manager->controls[0]);
        $this->assertSame('esc_url_raw', $manager->settings['dynamo_logo_image']['sanitize_callback']);
    }

    public function test_media_binding_uses_media_control_class(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'media'])))->apply($manager);
        $this->assertInstanceOf(WP_Customize_Media_Control::class, $manager->controls[0]);
    }

    public function test_media_binding_default_sanitizer_is_absint_callable(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'media'])))->apply($manager);
        $sanitize = $manager->settings['dynamo_logo_image']['sanitize_callback'];
        $this->assertIsCallable($sanitize);
        $this->assertSame(42, $sanitize('42'));
        // Must accept WP's two-arg call without throwing.
        $this->assertSame(7, $sanitize(7, new stdClass()));
    }

    public function test_url_default_is_empty_string(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'url'])))->apply($manager);
        $this->assertSame('', $manager->settings['dynamo_logo_image']['default']);
    }

    public function test_image_default_is_empty_string(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry()))->apply($manager);
        $this->assertSame('', $manager->settings['dynamo_logo_image']['default']);
    }

    public function test_media_default_is_zero(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['type' => 'media'])))->apply($manager);
        $this->assertSame(0, $manager->settings['dynamo_logo_image']['default']);
    }

    public function test_mime_type_passes_through_to_image_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry(['mime_type' => 'image'])))->apply($manager);
        $this->assertSame('image', $manager->controls[0]->args['mime_type']);
    }

    public function test_mime_type_passes_through_to_media_control(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry([
            'type'      => 'media',
            'mime_type' => 'video',
        ])))->apply($manager);
        $this->assertSame('video', $manager->controls[0]->args['mime_type']);
    }

    public function test_mime_type_omitted_is_absent_from_control_args(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->imageRegistry()))->apply($manager);
        $this->assertArrayNotHasKey('mime_type', $manager->controls[0]->args);
    }

    public function test_date_binding_uses_date_time_control_class(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'launch_at',
            'type'     => 'date',
            'label'    => 'Launch at',
            'section'  => 'banner',
            'selector' => '.banner::before',
            'property' => 'content',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertInstanceOf(WP_Customize_Date_Time_Control::class, $manager->controls[0]);
        $this->assertSame('sanitize_text_field', $manager->settings['dynamo_launch_at']['sanitize_callback']);
        $this->assertSame('', $manager->settings['dynamo_launch_at']['default']);
    }

    public function test_code_binding_uses_code_editor_control_class(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'custom_btn_css',
            'type'      => 'code',
            'code_type' => 'css',
            'label'     => 'Custom button CSS',
            'section'   => 'advanced',
            'selector'  => '.btn',
            'property'  => 'box-shadow',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertInstanceOf(WP_Customize_Code_Editor_Control::class, $manager->controls[0]);
        $this->assertSame('wp_kses_post', $manager->settings['dynamo_custom_btn_css']['sanitize_callback']);
        // Code bindings pre-fill the editor with `selector { property: ; }` so the linter passes.
        $this->assertSame(
            ".btn {\n    box-shadow: ;\n}",
            $manager->settings['dynamo_custom_btn_css']['default']
        );
    }

    public function test_code_binding_forwards_code_type_to_control_property(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'custom_btn_css',
            'type'      => 'code',
            'code_type' => 'css',
            'label'     => 'Custom button CSS',
            'section'   => 'advanced',
            'selector'  => '.btn',
            'property'  => 'box-shadow',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $control = $manager->controls[0];
        $this->assertSame('css', $control->code_type);
        $this->assertSame('css', $control->args['code_type']);
    }

    public function test_code_binding_default_is_full_block_for_linter(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'card_shadow',
            'type'      => 'code',
            'code_type' => 'css',
            'label'     => 'Card shadow',
            'section'   => 'advanced',
            'selector'  => '.card',
            'property'  => 'box-shadow',
            'default'   => '0 4px 12px rgba(0,0,0,0.15)',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertSame(
            ".card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n}",
            $manager->settings['dynamo_card_shadow']['default']
        );
    }

    public function test_code_binding_default_with_full_block_is_passed_through_unchanged(): void {
        // Dev explicitly provided a full block as default — adapter must not re-wrap.
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'card_shadow',
            'type'      => 'code',
            'code_type' => 'css',
            'label'     => 'Card shadow',
            'section'   => 'advanced',
            'selector'  => '.card',
            'property'  => 'box-shadow',
            'default'   => ".card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    transition: box-shadow 200ms ease;\n}",
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertSame(
            ".card {\n    box-shadow: 0 4px 12px rgba(0,0,0,0.15);\n    transition: box-shadow 200ms ease;\n}",
            $manager->settings['dynamo_card_shadow']['default']
        );
    }

    public function test_code_binding_with_empty_default_yields_empty_block_with_property(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'card_shadow',
            'type'      => 'code',
            'code_type' => 'css',
            'label'     => 'Card shadow',
            'section'   => 'advanced',
            'selector'  => '.card',
            'property'  => 'box-shadow',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        // No default value → editor still pre-fills with the property template so the linter passes.
        $this->assertSame(
            ".card {\n    box-shadow: ;\n}",
            $manager->settings['dynamo_card_shadow']['default']
        );
    }

    public function test_non_code_binding_default_is_unchanged(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->colorRegistry(['default' => '#abcdef'])))->apply($manager);
        $this->assertSame('#abcdef', $manager->settings['dynamo_header_bg']['default']);
    }

    public function test_code_binding_forwards_alternate_code_type(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'        => 'custom_js',
            'type'      => 'code',
            'code_type' => 'javascript',
            'label'     => 'Custom JS',
            'section'   => 'advanced',
            'selector'  => '.btn',
            'property'  => 'box-shadow',
        ]);
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($registry))->apply($manager);
        $this->assertSame('javascript', $manager->controls[0]->code_type);
    }

    public function test_radio_sanitizer_through_adapter_is_callable_whitelist(): void {
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter($this->radioRegistry()))->apply($manager);
        $sanitize = $manager->settings['dynamo_sidebar_layout']['sanitize_callback'];
        $this->assertIsCallable($sanitize);
        $this->assertSame('right', $sanitize('right'));
        $this->assertSame('left',  $sanitize('not-a-slug'));
        // WP calls sanitizers with ($value, $setting); must not throw.
        $this->assertSame('none', $sanitize('none', new stdClass()));
    }
}
