<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BindingCSSRendererTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']     = [];
        $GLOBALS['wp_theme_mods'] = [];
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
            'default'  => '#123456',
        ], $overrides));
        return $registry;
    }

    public function test_empty_registry_returns_empty_string(): void {
        $renderer = new Dynamo_Binding_CSS_Renderer(new Dynamo_Binding_Registry());
        $this->assertSame('', $renderer->render());
    }

    public function test_color_binding_emits_variable_layer_declaration(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->colorRegistry()))->render();
        $this->assertStringContainsString('--dynamo-header_bg: #123456;', $css);
    }

    public function test_color_binding_emits_rule_layer_declaration(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->colorRegistry()))->render();
        $this->assertStringContainsString('.site-header { background-color: var(--dynamo-header_bg); }', $css);
    }

    public function test_saved_theme_mod_overrides_default_in_variable_layer(): void {
        set_theme_mod('dynamo_header_bg', '#abcdef');
        $css = (new Dynamo_Binding_CSS_Renderer($this->colorRegistry()))->render();
        $this->assertStringContainsString('--dynamo-header_bg: #abcdef;', $css);
        $this->assertStringNotContainsString('#123456', $css);
    }

    public function test_variable_layer_is_wrapped_in_root_block(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->colorRegistry()))->render();
        $this->assertMatchesRegularExpression('/:root\s*\{[^}]*--dynamo-header_bg/s', $css);
    }

    public function test_renderer_suffixes_unit_in_variable_layer_when_set(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_pad',
            'type'     => 'range',
            'label'    => 'Header pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
            'unit'     => 'rem',
            'default'  => 1.5,
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString('--dynamo-header_pad: 1.5rem;', $css);
        $this->assertStringContainsString('.site-header { padding-block: var(--dynamo-header_pad); }', $css);
    }

    public function test_renderer_emits_bare_value_when_no_unit(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_opacity',
            'type'     => 'number',
            'label'    => 'Header opacity',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'opacity',
            'default'  => 0.5,
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString('--dynamo-header_opacity: 0.5;', $css);
    }

    public function test_renderer_does_not_double_suffix_when_saved_value_already_has_unit(): void {
        // If the user later switches to a saved string that already has a unit,
        // the renderer should not re-suffix. Tested via theme_mod containing e.g. "2rem".
        set_theme_mod('dynamo_header_pad', '2rem');
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_pad',
            'type'     => 'range',
            'label'    => 'Header pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
            'unit'     => 'rem',
            'default'  => 1.5,
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString('--dynamo-header_pad: 2rem;', $css);
        $this->assertStringNotContainsString('2remrem', $css);
    }

    public function test_multiple_bindings_each_emit_both_layers(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header bg',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
            'default'  => '#111111',
        ]);
        $registry->register([
            'id'       => 'footer_text',
            'type'     => 'color',
            'label'    => 'Footer text',
            'section'  => 'footer_styling',
            'selector' => '.site-footer',
            'property' => 'color',
            'default'  => '#222222',
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString('--dynamo-header_bg: #111111;', $css);
        $this->assertStringContainsString('--dynamo-footer_text: #222222;', $css);
        $this->assertStringContainsString('.site-header { background-color: var(--dynamo-header_bg); }', $css);
        $this->assertStringContainsString('.site-footer { color: var(--dynamo-footer_text); }', $css);
    }
}
