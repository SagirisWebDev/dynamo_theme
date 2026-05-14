<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../customizer/CustomizerTest.php';

class BindingEndToEndTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_theme_mods'] = [];
        $GLOBALS['wp_localized']  = [];
        Dynamo_Binding_Registry::reset_instance();
    }

    protected function tearDown(): void {
        Dynamo_Binding_Registry::reset_instance();
    }

    public function test_dynamo_config_customizer_global_function_exists(): void {
        $this->assertTrue(function_exists('dynamo_config_customizer'));
    }

    public function test_dynamo_config_customizer_registers_binding_via_singleton(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);
        $this->assertArrayHasKey('header_bg', Dynamo_Binding_Registry::instance()->all());
    }

    public function test_full_color_path_produces_variable_and_rule_layer_css(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
            'default'  => '#abcdef',
        ]);

        $renderer = new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance());
        $css = $renderer->render();

        $this->assertStringContainsString('--dynamo-header_bg: #abcdef;', $css);
        $this->assertStringContainsString('.site-header { background-color: var(--dynamo-header_bg); }', $css);
    }

    public function test_full_color_path_through_customizer_adapter_registers_color_control(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);

        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);

        $this->assertArrayHasKey('dynamo_header_bg', $manager->settings);
        $this->assertArrayHasKey('header_styling', $manager->sections);
        $this->assertCount(1, $manager->controls);
        $this->assertInstanceOf(WP_Customize_Color_Control::class, $manager->controls[0]);
    }

    public function test_duplicate_id_through_global_throws(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);

        $this->expectException(InvalidArgumentException::class);
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);
    }

    public function test_invalid_args_through_global_throw(): void {
        $this->expectException(InvalidArgumentException::class);
        dynamo_config_customizer(['id' => 'broken']);
    }

    public function test_preview_bridge_produces_metadata_for_registered_binding(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);

        $map = (new Dynamo_Binding_Preview_Bridge(Dynamo_Binding_Registry::instance()))->build_metadata();
        $this->assertArrayHasKey('dynamo_header_bg', $map);
        $this->assertSame('.site-header', $map['dynamo_header_bg']['selector']);
        $this->assertSame('background-color', $map['dynamo_header_bg']['property']);
    }

    public function test_text_binding_produces_variable_and_rule_layer_with_string_value(): void {
        dynamo_config_customizer([
            'id'       => 'heading_font',
            'type'     => 'text',
            'label'    => 'Heading font',
            'section'  => 'typography',
            'selector' => 'h1, h2, h3',
            'property' => 'font-family',
            'default'  => 'Georgia, serif',
        ]);

        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-heading_font: Georgia, serif;', $css);
        $this->assertStringContainsString('h1, h2, h3 { font-family: var(--dynamo-heading_font); }', $css);
    }

    public function test_text_binding_through_adapter_uses_generic_text_control(): void {
        dynamo_config_customizer([
            'id'       => 'heading_font',
            'type'     => 'text',
            'label'    => 'Heading font',
            'section'  => 'typography',
            'selector' => 'h1, h2, h3',
            'property' => 'font-family',
        ]);

        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);

        $this->assertSame('text', $manager->controls[0]->args['type']);
        $this->assertSame('sanitize_text_field', $manager->settings['dynamo_heading_font']['sanitize_callback']);
    }

    public function test_textarea_binding_full_path_through_global_function(): void {
        dynamo_config_customizer([
            'id'       => 'before_text',
            'type'     => 'textarea',
            'label'    => 'Before-text',
            'section'  => 'banner',
            'selector' => '.banner::before',
            'property' => 'content',
            'default'  => '"Hello"',
        ]);

        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);

        $this->assertSame('textarea', $manager->controls[0]->args['type']);
        $this->assertSame('sanitize_textarea_field', $manager->settings['dynamo_before_text']['sanitize_callback']);

        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-before_text: "Hello";', $css);
        $this->assertStringContainsString('.banner::before { content: var(--dynamo-before_text); }', $css);
    }

    public function test_text_binding_preview_metadata_has_no_unit_or_choices(): void {
        dynamo_config_customizer([
            'id'       => 'heading_font',
            'type'     => 'text',
            'label'    => 'Heading font',
            'section'  => 'typography',
            'selector' => 'h1, h2, h3',
            'property' => 'font-family',
        ]);
        $entry = (new Dynamo_Binding_Preview_Bridge(Dynamo_Binding_Registry::instance()))
            ->build_metadata()['dynamo_heading_font'];
        $this->assertSame('h1, h2, h3', $entry['selector']);
        $this->assertSame('font-family', $entry['property']);
        $this->assertArrayNotHasKey('unit', $entry);
        $this->assertArrayNotHasKey('choicesMap', $entry);
    }

    public function test_range_binding_full_path_with_unit_suffix(): void {
        dynamo_config_customizer([
            'id'          => 'header_pad',
            'type'        => 'range',
            'label'       => 'Header pad',
            'section'     => 'header_styling',
            'selector'    => '.site-header',
            'property'    => 'padding-block',
            'unit'        => 'rem',
            'default'     => 1.5,
            'input_attrs' => ['min' => 0, 'max' => 6, 'step' => 0.25],
        ]);

        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);

        $this->assertSame('range', $manager->controls[0]->args['type']);
        $this->assertSame(
            ['min' => 0, 'max' => 6, 'step' => 0.25],
            $manager->controls[0]->args['input_attrs']
        );
        $sanitizer = $manager->settings['dynamo_header_pad']['sanitize_callback'];
        $this->assertIsCallable($sanitizer);
        $this->assertSame(1.5, $sanitizer('1.5', new stdClass()));

        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-header_pad: 1.5rem;', $css);
        $this->assertStringContainsString('.site-header { padding-block: var(--dynamo-header_pad); }', $css);

        $entry = (new Dynamo_Binding_Preview_Bridge(Dynamo_Binding_Registry::instance()))
            ->build_metadata()['dynamo_header_pad'];
        $this->assertSame('rem', $entry['unit']);
    }

    public function test_number_binding_full_path_without_unit_for_opacity(): void {
        dynamo_config_customizer([
            'id'       => 'header_opacity',
            'type'     => 'number',
            'label'    => 'Header opacity',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'opacity',
            'default'  => 0.8,
        ]);

        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);
        $this->assertSame('number', $manager->controls[0]->args['type']);

        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-header_opacity: 0.8;', $css);
        $this->assertStringNotContainsString('0.8rem', $css);
    }

    public function test_number_with_length_property_rejected_without_unit(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/incompatible/i');
        dynamo_config_customizer([
            'id'       => 'broken_pad',
            'type'     => 'number',
            'label'    => 'Broken pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
        ]);
    }

    public function test_number_with_bad_unit_rejected(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unit/i');
        dynamo_config_customizer([
            'id'       => 'broken_pad',
            'type'     => 'range',
            'label'    => 'Broken pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
            'unit'     => 'pxx',
        ]);
    }

    public function test_css_generator_merges_binding_variables_into_single_root_block(): void {
        dynamo_config_customizer([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
            'default'  => '#123abc',
        ]);

        $generator = new Dynamo_CSS_Generator(new Dynamo_Token_Registry());
        $css = $generator->generate();

        $this->assertStringContainsString('--dynamo-colors-primary', $css);
        $this->assertStringContainsString('--dynamo-header_bg: #123abc;', $css);
        $this->assertStringContainsString('.site-header { background-color: var(--dynamo-header_bg); }', $css);

        $this->assertSame(1, substr_count($css, ':root'), 'expected a single merged :root block');
        $this->assertMatchesRegularExpression(
            '/:root\s*\{[^}]*--dynamo-colors-primary[^}]*--dynamo-header_bg[^}]*\}/s',
            $css
        );
    }

    public function test_radio_binding_full_path_with_choices(): void {
        dynamo_config_customizer([
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
        ]);

        // Default slug 'left' → resolved CSS value in the Variable layer.
        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-sidebar_layout: 300px 1fr;', $css);
        $this->assertStringContainsString(
            '.site-content { grid-template-columns: var(--dynamo-sidebar_layout); }',
            $css
        );

        // Saving a non-default slug resolves to its choice value.
        set_theme_mod('dynamo_sidebar_layout', 'right');
        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $this->assertStringContainsString('--dynamo-sidebar_layout: 1fr 300px;', $css);

        // Adapter wires a radio WP control with a flattened slug=>label choices.
        $manager = new FakeCustomizeManager();
        (new Dynamo_Customizer_Binding_Adapter(Dynamo_Binding_Registry::instance()))
            ->apply($manager);
        $this->assertSame('radio', $manager->controls[0]->args['type']);
        $this->assertSame(
            ['left' => 'Left', 'right' => 'Right', 'none' => 'None'],
            $manager->controls[0]->args['choices']
        );

        // Sanitize callback accepts WP's two-arg call and rejects bad slugs.
        $sanitize = $manager->settings['dynamo_sidebar_layout']['sanitize_callback'];
        $this->assertIsCallable($sanitize);
        $this->assertSame('right', $sanitize('right', new stdClass()));
        $this->assertSame('left',  $sanitize('not-a-slug', new stdClass()));

        // Preview bridge emits choicesMap (slug → value) for the JS layer.
        $entry = (new Dynamo_Binding_Preview_Bridge(Dynamo_Binding_Registry::instance()))
            ->build_metadata()['dynamo_sidebar_layout'];
        $this->assertSame(
            ['left' => '300px 1fr', 'right' => '1fr 300px', 'none' => '1fr'],
            $entry['choicesMap']
        );
    }

    public function test_radio_without_choices_through_global_function_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/choices/i');
        dynamo_config_customizer([
            'id'       => 'broken_radio',
            'type'     => 'radio',
            'label'    => 'Broken',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
        ]);
    }

    public function test_select_with_flat_choices_through_global_function_throws(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/choices/i');
        dynamo_config_customizer([
            'id'       => 'broken_select',
            'type'     => 'select',
            'label'    => 'Broken',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
            'choices'  => ['left' => 'Left', 'right' => 'Right'],
        ]);
    }

    public function test_grid_binding_without_requires_throws_through_global_function(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/display: grid/i');
        dynamo_config_customizer([
            'id'       => 'broken_grid',
            'type'     => 'radio',
            'label'    => 'Broken',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
            'choices'  => [
                'left' => ['label' => 'Left', 'value' => '300px 1fr'],
            ],
        ]);
    }

    public function test_parent_prereq_property_throws_through_global_function(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/parent/i');
        dynamo_config_customizer([
            'id'       => 'card_span',
            'type'     => 'select',
            'label'    => 'Card span',
            'section'  => 'layout',
            'selector' => '.card',
            'property' => 'grid-column',
            'choices'  => [
                'half' => ['label' => 'Half', 'value' => 'span 2'],
            ],
        ]);
    }

    public function test_full_requires_path_emits_prereq_rule_and_var_rule(): void {
        dynamo_config_customizer([
            'id'       => 'sidebar_layout',
            'type'     => 'radio',
            'label'    => 'Sidebar layout',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
            'requires' => ['display' => 'grid'],
            'choices'  => [
                'left' => ['label' => 'Left', 'value' => '300px 1fr'],
                'full' => ['label' => 'Full', 'value' => '1fr'],
            ],
        ]);

        $css = (new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance()))->render();
        $prereq = strpos($css, '.site-content { display: grid; }');
        $var    = strpos($css, '.site-content { grid-template-columns: var(--dynamo-sidebar_layout); }');
        $this->assertNotFalse($prereq);
        $this->assertNotFalse($var);
        $this->assertLessThan($var, $prereq);
    }
}
