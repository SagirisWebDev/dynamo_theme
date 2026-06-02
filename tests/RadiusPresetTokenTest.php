<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #36 — PRD v1.3.0 Slice 3: Radius Preset tracer — "Large" option end-to-end
 *
 * PHPUnit tests covering:
 *   AC1 — Token registry registers `borders-radius-lg` with a default of `0.5rem`.
 *   AC2 — The dynamic CSS output includes `--dynamo-borders-radius-lg: 0.5rem;`.
 *   AC3 — `dynamo_border_radius_presets()` function exists, calls `apply_filters`,
 *          and returns the correct shape:
 *          `['lg' => ['label' => 'Large', 'default' => '0.5rem']]`.
 */
class RadiusPresetTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_filter']         = [];
        $GLOBALS['wp_doing_it_wrong'] = [];
        $GLOBALS['wp_theme_mods']     = [];
    }

    // -------------------------------------------------------------------------
    // AC1 — Token registry registers borders-radius-lg at 0.5rem
    // -------------------------------------------------------------------------

    /** @test */
    public function token_registry_registers_borders_radius_lg(): void
    {
        $registry = new Dynamo_Token_Registry();
        $all      = $registry->all();

        $this->assertArrayHasKey(
            'borders-radius-lg',
            $all,
            'Token registry must include the key "borders-radius-lg".'
        );
    }

    /** @test */
    public function token_registry_borders_radius_lg_default_is_0_5rem(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '0.5rem',
            $registry->get('borders-radius-lg'),
            'Default value for "borders-radius-lg" must be "0.5rem".'
        );
    }

    /** @test */
    public function token_registry_get_borders_radius_lg_returns_non_null(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertNotNull(
            $registry->get('borders-radius-lg'),
            'Dynamo_Token_Registry::get("borders-radius-lg") must not return null.'
        );
    }

    // -------------------------------------------------------------------------
    // AC2 — CSS generator emits --dynamo-borders-radius-lg: 0.5rem;
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
    public function css_generator_emits_borders_radius_lg_custom_property(): void
    {
        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString(
            '--dynamo-borders-radius-lg',
            $css,
            'Generated CSS must contain the custom property --dynamo-borders-radius-lg.'
        );
    }

    /** @test */
    public function css_generator_emits_borders_radius_lg_with_value_0_5rem(): void
    {
        $css = $this->makeGenerator()->generate();

        $this->assertStringContainsString(
            '--dynamo-borders-radius-lg: 0.5rem;',
            $css,
            'Generated CSS must contain exactly "--dynamo-borders-radius-lg: 0.5rem;".'
        );
    }

    // -------------------------------------------------------------------------
    // AC3 — dynamo_border_radius_presets() filter shape
    // -------------------------------------------------------------------------

    /** @test */
    public function dynamo_border_radius_presets_function_exists(): void
    {
        $this->assertTrue(
            function_exists('dynamo_border_radius_presets'),
            'A public function dynamo_border_radius_presets() must exist.'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_filter_is_applied(): void
    {
        // Production code must call apply_filters('dynamo_border_radius_presets', ...)
        // internally. We verify this by registering a listener and calling the
        // public API function; if the function does not exist or does not call
        // apply_filters, the filter will never run.
        $called = false;
        add_filter('dynamo_border_radius_presets', function (array $presets) use (&$called): array {
            $called = true;
            return $presets;
        });

        // This must call the real production function, not apply_filters directly.
        dynamo_border_radius_presets();

        $this->assertTrue(
            $called,
            'dynamo_border_radius_presets() must internally call apply_filters("dynamo_border_radius_presets", ...).'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_returns_array(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertIsArray(
            $presets,
            'dynamo_border_radius_presets() must return an array.'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_contains_lg_key(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertArrayHasKey(
            'lg',
            $presets,
            'dynamo_border_radius_presets() must have a "lg" key.'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_lg_has_label_key(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertArrayHasKey(
            'label',
            $presets['lg'],
            'The "lg" preset must have a "label" key.'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_lg_label_is_Large(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'Large',
            $presets['lg']['label'],
            'The "lg" preset label must be "Large".'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_lg_has_default_key(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertArrayHasKey(
            'default',
            $presets['lg'],
            'The "lg" preset must have a "default" key.'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_lg_default_is_0_5rem(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            '0.5rem',
            $presets['lg']['default'],
            'The "lg" preset default must be "0.5rem".'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_out_of_the_box_has_only_lg(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertCount(
            1,
            $presets,
            'Out-of-the-box dynamo_border_radius_presets() must return exactly one preset (lg).'
        );
    }

    /** @test */
    public function dynamo_border_radius_presets_filter_allows_adding_new_presets(): void
    {
        add_filter('dynamo_border_radius_presets', function (array $presets): array {
            $presets['xl'] = ['label' => 'Extra Large', 'default' => '1rem'];
            return $presets;
        });

        $presets = dynamo_border_radius_presets();

        $this->assertArrayHasKey(
            'xl',
            $presets,
            'Consumers must be able to add presets via the dynamo_border_radius_presets filter.'
        );
    }
}
