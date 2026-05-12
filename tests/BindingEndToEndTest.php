<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/CustomizerTest.php';

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

    public function test_css_generator_appends_binding_output_after_token_root_block(): void {
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
    }
}
