<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #34 — PRD v1.3.0 Slice 1: Width Preset tracer — "Narrow" option end-to-end
 *
 * PHPUnit tests covering:
 *   AC1 — Token registry registers `layout-width-narrow` with a default of `640px`.
 *   AC2 — The dynamic CSS output includes `--dynamo-layout-width-narrow: 640px;`.
 *   AC3 — `dynamo_layout_width_presets` filter is applied and returns the correct shape.
 */
class WidthPresetTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_filter']         = [];
        $GLOBALS['wp_doing_it_wrong'] = [];
        $GLOBALS['wp_theme_mods']     = [];
    }

    // -------------------------------------------------------------------------
    // AC1 — Token registry registers layout-width-narrow at 640px
    // -------------------------------------------------------------------------

    /** @test */
    public function token_registry_registers_layout_width_narrow(): void
    {
        $registry = new Dynamo_Token_Registry();
        $all      = $registry->all();

        $this->assertArrayHasKey(
            'layout-width-narrow',
            $all,
            'Token registry must include the key "layout-width-narrow".'
        );
    }

    /** @test */
    public function token_registry_layout_width_narrow_default_is_640px(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '640px',
            $registry->get('layout-width-narrow'),
            'Default value for "layout-width-narrow" must be "640px".'
        );
    }

    /** @test */
    public function token_registry_get_layout_width_narrow_returns_non_null(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertNotNull(
            $registry->get('layout-width-narrow'),
            'Dynamo_Token_Registry::get("layout-width-narrow") must not return null.'
        );
    }

    // -------------------------------------------------------------------------
    // AC2 — CSS generator emits --dynamo-layout-width-narrow: 640px;
    // -------------------------------------------------------------------------

    private function fixtureManifest(): Dynamo_Font_Manifest
    {
        return new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
    }

    private function makeGenerator(): Dynamo_CSS_Generator
    {
        return new Dynamo_CSS_Generator(new Dynamo_Token_Registry(), $this->fixtureManifest());
    }

    /** @test */
    public function css_generator_emits_layout_width_narrow_custom_property(): void
    {
        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString(
            '--dynamo-layout-width-narrow',
            $css,
            'Generated CSS must contain the custom property --dynamo-layout-width-narrow.'
        );
    }

    /** @test */
    public function css_generator_emits_layout_width_narrow_with_value_640px(): void
    {
        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString(
            '--dynamo-layout-width-narrow: 640px;',
            $css,
            'Generated CSS must contain exactly "--dynamo-layout-width-narrow: 640px;".'
        );
    }

    /** @test */
    public function css_generator_layout_width_narrow_respects_theme_mod_override(): void
    {
        $GLOBALS['wp_theme_mods']['dynamo_layout_width_narrow'] = '800px';

        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString(
            '--dynamo-layout-width-narrow: 800px;',
            $css,
            'CSS generator must use the saved theme_mod value when it overrides the default.'
        );
        $this->assertStringNotContainsString(
            '--dynamo-layout-width-narrow: 640px;',
            $css,
            'CSS generator must NOT emit the default 640px when theme_mod overrides it.'
        );
    }

    // -------------------------------------------------------------------------
    // AC3 — dynamo_layout_width_presets filter shape
    // -------------------------------------------------------------------------

    /** @test */
    public function dynamo_layout_width_presets_filter_is_applied(): void
    {
        // Production code must call apply_filters('dynamo_layout_width_presets', ...)
        // internally. We verify this by registering a listener and calling the
        // public API function; if the function does not exist or does not call
        // apply_filters, the filter will never run.
        $called = false;
        add_filter('dynamo_layout_width_presets', function (array $presets) use (&$called): array {
            $called = true;
            return $presets;
        });

        // This must call the real production function, not apply_filters directly.
        dynamo_layout_width_presets();

        $this->assertTrue(
            $called,
            'dynamo_layout_width_presets() must internally call apply_filters("dynamo_layout_width_presets", ...).'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_default_value_is_array(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertIsArray(
            $presets,
            'dynamo_layout_width_presets must return an array.'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_contains_narrow_key(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertArrayHasKey(
            'narrow',
            $presets,
            'dynamo_layout_width_presets must have a "narrow" key.'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_narrow_has_label_key(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertArrayHasKey(
            'label',
            $presets['narrow'],
            'The "narrow" preset must have a "label" key.'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_narrow_label_is_Narrow(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertSame(
            'Narrow',
            $presets['narrow']['label'],
            'The "narrow" preset label must be "Narrow".'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_narrow_has_default_key(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertArrayHasKey(
            'default',
            $presets['narrow'],
            'The "narrow" preset must have a "default" key.'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_narrow_default_is_640px(): void
    {
        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertSame(
            '640px',
            $presets['narrow']['default'],
            'The "narrow" preset default must be "640px".'
        );
    }

    /** @test */
    public function dynamo_layout_width_presets_filter_allows_adding_new_presets(): void
    {
        add_filter('dynamo_layout_width_presets', function (array $presets): array {
            $presets['wide'] = ['label' => 'Wide', 'default' => '1200px'];
            return $presets;
        });

        $presets = apply_filters('dynamo_layout_width_presets', $this->getDefaultPresets());

        $this->assertArrayHasKey(
            'wide',
            $presets,
            'Consumers must be able to add presets via the dynamo_layout_width_presets filter.'
        );
    }

    // -------------------------------------------------------------------------
    // Helper: returns the out-of-the-box default preset array
    // -------------------------------------------------------------------------

    /**
     * This mirrors what the production code SHOULD return as the base value
     * passed into apply_filters('dynamo_layout_width_presets', ...).
     * Before the feature is implemented, calling the real function that provides
     * this value would not exist — so these tests will fail at that call site.
     *
     * Once implemented, replace this helper with a call to the real function,
     * e.g. dynamo_get_layout_width_presets() or however the production code
     * exposes the default.
     */
    private function getDefaultPresets(): array
    {
        // This is the shape the production code must provide.
        // Tests asserting filter behaviour call this to seed apply_filters().
        // The real production code will call apply_filters internally, so these
        // tests will break in the right way (function undefined) until AC3 is
        // implemented.
        return dynamo_layout_width_presets();
    }
}
