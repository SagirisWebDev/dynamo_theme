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

    public function test_radio_variable_layer_emits_resolved_css_value_not_slug(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        // Default is 'left' → '300px 1fr' must be in the variable, not the slug.
        $this->assertStringContainsString('--dynamo-sidebar_layout: 300px 1fr;', $css);
        $this->assertStringNotContainsString('--dynamo-sidebar_layout: left;', $css);
    }

    public function test_radio_saved_slug_resolves_to_its_choice_value(): void {
        set_theme_mod('dynamo_sidebar_layout', 'right');
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        $this->assertStringContainsString('--dynamo-sidebar_layout: 1fr 300px;', $css);
    }

    public function test_radio_invalid_saved_slug_falls_back_to_default_choice_value(): void {
        set_theme_mod('dynamo_sidebar_layout', 'not-a-slug');
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        // Default 'left' → '300px 1fr'.
        $this->assertStringContainsString('--dynamo-sidebar_layout: 300px 1fr;', $css);
    }

    public function test_select_variable_layer_emits_resolved_css_value(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry(['type' => 'select'])))->render();
        $this->assertStringContainsString('--dynamo-sidebar_layout: 300px 1fr;', $css);
    }

    public function test_radio_rule_layer_uses_var_reference_not_resolved_value(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        $this->assertStringContainsString('.site-content { grid-template-columns: var(--dynamo-sidebar_layout); }', $css);
    }

    public function test_requires_emits_prereq_rule_on_same_selector(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        $this->assertStringContainsString('.site-content { display: grid; }', $css);
    }

    public function test_requires_prereq_rule_appears_before_var_rule(): void {
        $css      = (new Dynamo_Binding_CSS_Renderer($this->radioRegistry()))->render();
        $prereq   = strpos($css, '.site-content { display: grid; }');
        $var_rule = strpos($css, '.site-content { grid-template-columns: var(--dynamo-sidebar_layout); }');
        $this->assertNotFalse($prereq);
        $this->assertNotFalse($var_rule);
        $this->assertLessThan($var_rule, $prereq);
    }

    public function test_two_bindings_on_same_selector_with_same_requires_emit_prereq_once(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'sidebar_layout',
            'type'     => 'radio',
            'label'    => 'Sidebar layout',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-columns',
            'requires' => ['display' => 'grid'],
            'choices'  => [
                'left' => ['label' => 'Left', 'value' => '300px 1fr'],
            ],
        ]);
        $registry->register([
            'id'       => 'sidebar_rows',
            'type'     => 'radio',
            'label'    => 'Sidebar rows',
            'section'  => 'layout',
            'selector' => '.site-content',
            'property' => 'grid-template-rows',
            'requires' => ['display' => 'grid'],
            'choices'  => [
                'single' => ['label' => 'Single', 'value' => 'auto'],
            ],
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertSame(
            1,
            substr_count($css, '.site-content { display: grid; }'),
            'expected display: grid emitted exactly once for two bindings on the same selector'
        );
    }

    public function test_property_without_requirement_does_not_emit_extra_rule(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->colorRegistry()))->render();
        $this->assertStringNotContainsString('display:', $css);
        $this->assertStringNotContainsString('position:', $css);
    }

    private function mediaRegistry(array $overrides = []): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register(array_merge([
            'id'       => 'hero_bg',
            'type'     => 'media',
            'label'    => 'Hero background',
            'section'  => 'hero',
            'selector' => '.hero',
            'property' => 'background-image',
        ], $overrides));
        return $registry;
    }

    public function test_media_binding_resolves_attachment_id_to_url(): void {
        $GLOBALS['wp_attachment_urls'] = [42 => 'http://localhost/wp-content/uploads/hero.jpg'];
        set_theme_mod('dynamo_hero_bg', 42);
        $css = (new Dynamo_Binding_CSS_Renderer($this->mediaRegistry()))->render();
        $this->assertStringContainsString(
            "--dynamo-hero_bg: url('http://localhost/wp-content/uploads/hero.jpg');",
            $css
        );
    }

    public function test_media_binding_with_zero_default_emits_empty(): void {
        $css = (new Dynamo_Binding_CSS_Renderer($this->mediaRegistry()))->render();
        // Default is 0; resolve_value emits '' for a missing attachment, no url() wrap.
        $this->assertStringContainsString('--dynamo-hero_bg: ;', $css);
        $this->assertStringNotContainsString('--dynamo-hero_bg: 0;', $css);
        $this->assertStringNotContainsString("url('')", $css);
    }

    public function test_media_binding_unknown_attachment_id_emits_empty(): void {
        $GLOBALS['wp_attachment_urls'] = [];
        set_theme_mod('dynamo_hero_bg', 999);
        $css = (new Dynamo_Binding_CSS_Renderer($this->mediaRegistry()))->render();
        $this->assertStringContainsString('--dynamo-hero_bg: ;', $css);
    }

    public function test_url_binding_wraps_default_url_with_css_url(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'logo_url',
            'type'     => 'url',
            'label'    => 'Logo URL',
            'section'  => 'branding',
            'selector' => '.site-logo',
            'property' => 'background-image',
            'default'  => 'http://localhost/logo.png',
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString("--dynamo-logo_url: url('http://localhost/logo.png');", $css);
    }

    public function test_image_binding_wraps_default_url_with_css_url(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'logo_image',
            'type'     => 'image',
            'label'    => 'Logo image',
            'section'  => 'branding',
            'selector' => '.site-logo',
            'property' => 'background-image',
            'default'  => 'http://localhost/logo.png',
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString("--dynamo-logo_image: url('http://localhost/logo.png');", $css);
    }

    public function test_url_binding_does_not_double_wrap_already_wrapped_value(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'logo_url',
            'type'     => 'url',
            'label'    => 'Logo URL',
            'section'  => 'branding',
            'selector' => '.site-logo',
            'property' => 'background-image',
            'default'  => "url('http://localhost/logo.png')",
        ]);
        $css = (new Dynamo_Binding_CSS_Renderer($registry))->render();
        $this->assertStringContainsString("--dynamo-logo_url: url('http://localhost/logo.png');", $css);
        $this->assertStringNotContainsString("url('url(", $css);
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
