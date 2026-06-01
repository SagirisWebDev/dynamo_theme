<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #35 — PRD v1.3.0 Slice 2: Complete Width Scale + alias helper + Customizer subsection
 *
 * PHPUnit tests covering:
 *   AC1 — Token registry registers all five width steps.
 *   AC2 — Dynamo_Token_Registry::resolve_alias() resolves alias and non-alias steps.
 *   AC3 — dynamo_layout_width_presets() returns the full canonical five-step list.
 *   AC5 — Customizer "Width Scale" subsection appears with controls for narrow/wide/full only.
 *   AC6 — Adding a step via filter causes a Customizer control to appear automatically.
 *   AC7 — Alias steps resolve dynamically when the underlying role token changes.
 */
class LayoutWidthScaleTest extends TestCase
{
    use MakesCustomizer;

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']         = [];
        $GLOBALS['wp_doing_it_wrong'] = [];
        $GLOBALS['wp_theme_mods']     = [];
    }

    // =========================================================================
    // AC1 — Token Registry registers all five width steps
    // =========================================================================

    /** @test */
    public function token_registry_registers_layout_width_default(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'layout-width-default',
            $registry->all(),
            'Token registry must include the key "layout-width-default".'
        );
    }

    /** @test */
    public function token_registry_registers_layout_width_wide(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'layout-width-wide',
            $registry->all(),
            'Token registry must include the key "layout-width-wide".'
        );
    }

    /** @test */
    public function token_registry_layout_width_wide_default_is_1024px(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '1024px',
            $registry->get('layout-width-wide'),
            'Default value for "layout-width-wide" must be "1024px".'
        );
    }

    /** @test */
    public function token_registry_registers_layout_width_container(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'layout-width-container',
            $registry->all(),
            'Token registry must include the key "layout-width-container".'
        );
    }

    /** @test */
    public function token_registry_registers_layout_width_full(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'layout-width-full',
            $registry->all(),
            'Token registry must include the key "layout-width-full".'
        );
    }

    /** @test */
    public function token_registry_layout_width_full_default_is_100_percent(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '100%',
            $registry->get('layout-width-full'),
            'Default value for "layout-width-full" must be "100%".'
        );
    }

    /** @test */
    public function token_registry_all_five_width_steps_are_present(): void
    {
        $registry = new Dynamo_Token_Registry();
        $all      = $registry->all();

        $expected = [
            'layout-width-narrow',
            'layout-width-default',
            'layout-width-wide',
            'layout-width-container',
            'layout-width-full',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $all, "Missing token key \"{$key}\" from the registry.");
        }
    }

    // =========================================================================
    // AC2 — resolve_alias() method
    // =========================================================================

    /** @test */
    public function token_registry_has_resolve_alias_method(): void
    {
        $this->assertTrue(
            method_exists('Dynamo_Token_Registry', 'resolve_alias'),
            'Dynamo_Token_Registry must have a public static or instance method resolve_alias().'
        );
    }

    /** @test */
    public function resolve_alias_returns_content_width_value_for_layout_width_default(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-default');
        $expected = $registry->get('layout-content-width');

        $this->assertNotNull(
            $resolved,
            'resolve_alias("layout-width-default") must not return null.'
        );
        $this->assertSame(
            $expected,
            $resolved,
            'resolve_alias("layout-width-default") must resolve to the value of the "layout-content-width" token.'
        );
    }

    /** @test */
    public function resolve_alias_returns_container_max_width_value_for_layout_width_container(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-container');
        $expected = $registry->get('layout-container-max-width');

        $this->assertNotNull(
            $resolved,
            'resolve_alias("layout-width-container") must not return null.'
        );
        $this->assertSame(
            $expected,
            $resolved,
            'resolve_alias("layout-width-container") must resolve to the value of the "layout-container-max-width" token.'
        );
    }

    /** @test */
    public function resolve_alias_returns_registered_value_for_non_alias_narrow(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-narrow');

        $this->assertSame(
            '640px',
            $resolved,
            'resolve_alias("layout-width-narrow") must return the registered default value "640px".'
        );
    }

    /** @test */
    public function resolve_alias_returns_registered_value_for_non_alias_wide(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-wide');

        $this->assertSame(
            '1024px',
            $resolved,
            'resolve_alias("layout-width-wide") must return the registered default value "1024px".'
        );
    }

    /** @test */
    public function resolve_alias_returns_registered_value_for_non_alias_full(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-full');

        $this->assertSame(
            '100%',
            $resolved,
            'resolve_alias("layout-width-full") must return the registered default value "100%".'
        );
    }

    // =========================================================================
    // AC3 — dynamo_layout_width_presets() returns full canonical five-step list
    // =========================================================================

    /** @test */
    public function layout_width_presets_returns_all_five_steps(): void
    {
        $presets = dynamo_layout_width_presets();

        $expected_keys = ['narrow', 'default', 'wide', 'container', 'full'];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $presets,
                "dynamo_layout_width_presets() must contain the \"{$key}\" key."
            );
        }
    }

    /** @test */
    public function layout_width_presets_has_exactly_five_steps(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertCount(
            5,
            $presets,
            'dynamo_layout_width_presets() must return exactly 5 entries (narrow, default, wide, container, full).'
        );
    }

    /** @test */
    public function layout_width_presets_default_has_label_Default(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            'Default',
            $presets['default']['label'],
            'The "default" preset label must be "Default".'
        );
    }

    /** @test */
    public function layout_width_presets_wide_has_label_Wide(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            'Wide',
            $presets['wide']['label'],
            'The "wide" preset label must be "Wide".'
        );
    }

    /** @test */
    public function layout_width_presets_wide_has_default_of_1024px(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            '1024px',
            $presets['wide']['default'],
            'The "wide" preset default must be "1024px".'
        );
    }

    /** @test */
    public function layout_width_presets_container_has_label_Container(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            'Container',
            $presets['container']['label'],
            'The "container" preset label must be "Container".'
        );
    }

    /** @test */
    public function layout_width_presets_full_has_label_Full(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            'Full',
            $presets['full']['label'],
            'The "full" preset label must be "Full".'
        );
    }

    /** @test */
    public function layout_width_presets_full_has_default_of_100_percent(): void
    {
        $presets = dynamo_layout_width_presets();

        $this->assertSame(
            '100%',
            $presets['full']['default'],
            'The "full" preset default must be "100%".'
        );
    }

    /** @test */
    public function layout_width_presets_each_entry_has_label_and_default_keys(): void
    {
        $presets = dynamo_layout_width_presets();

        foreach ($presets as $slug => $data) {
            $this->assertArrayHasKey(
                'label',
                $data,
                "Preset \"{$slug}\" must have a 'label' key."
            );
            $this->assertArrayHasKey(
                'default',
                $data,
                "Preset \"{$slug}\" must have a 'default' key."
            );
            $this->assertIsString(
                $data['label'],
                "Preset \"{$slug}\" 'label' must be a string."
            );
            $this->assertIsString(
                $data['default'],
                "Preset \"{$slug}\" 'default' must be a string."
            );
        }
    }

    // =========================================================================
    // AC5 — Customizer "Width Scale" subsection with narrow/wide/full controls only
    // =========================================================================

    /** @test */
    public function customizer_register_adds_width_scale_section(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey(
            'dynamo_layout_width_scale',
            $manager->sections,
            'Customizer must add a "dynamo_layout_width_scale" section.'
        );
    }

    /** @test */
    public function customizer_width_scale_section_belongs_to_layout_panel(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertSame(
            'dynamo_layout',
            $manager->sections['dynamo_layout_width_scale']['panel'],
            'The "dynamo_layout_width_scale" section must belong to the "dynamo_layout" panel.'
        );
    }

    /** @test */
    public function customizer_width_scale_has_control_for_narrow(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_layout_width_narrow',
            $control_ids,
            'Customizer must add a control with id "dynamo_layout_width_narrow".'
        );
    }

    /** @test */
    public function customizer_width_scale_has_control_for_wide(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_layout_width_wide',
            $control_ids,
            'Customizer must add a control with id "dynamo_layout_width_wide".'
        );
    }

    /** @test */
    public function customizer_width_scale_has_control_for_full(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_layout_width_full',
            $control_ids,
            'Customizer must add a control with id "dynamo_layout_width_full".'
        );
    }

    /** @test */
    public function customizer_width_scale_does_not_add_control_for_default_alias(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertNotContains(
            'dynamo_layout_width_default',
            $control_ids,
            'Customizer must NOT add a control for alias step "default" — it inherits from "layout-content-width".'
        );
    }

    /** @test */
    public function customizer_width_scale_does_not_add_control_for_container_alias(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertNotContains(
            'dynamo_layout_width_container',
            $control_ids,
            'Customizer must NOT add a control for alias step "container" — it inherits from "layout-container-max-width".'
        );
    }

    /** @test */
    public function customizer_width_scale_controls_are_in_width_scale_section(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $expected_control_ids = [
            'dynamo_layout_width_narrow',
            'dynamo_layout_width_wide',
            'dynamo_layout_width_full',
        ];

        $width_scale_controls = array_filter(
            $manager->controls,
            fn($c) => in_array($c->id, $expected_control_ids, true)
        );

        // Must find all three non-alias controls (fails RED until they are registered)
        $this->assertCount(
            3,
            $width_scale_controls,
            'Customizer must register exactly 3 controls in the width scale section (narrow, wide, full).'
        );

        foreach ($width_scale_controls as $control) {
            $this->assertSame(
                'dynamo_layout_width_scale',
                $control->args['section'] ?? null,
                "Control {$control->id} must be assigned to the 'dynamo_layout_width_scale' section."
            );
        }
    }

    /** @test */
    public function customizer_width_scale_settings_use_postmessage_transport(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        foreach (['dynamo_layout_width_narrow', 'dynamo_layout_width_wide', 'dynamo_layout_width_full'] as $setting_id) {
            $this->assertArrayHasKey(
                $setting_id,
                $manager->settings,
                "Setting \"{$setting_id}\" must be registered in the Customizer."
            );
            $this->assertSame(
                'postMessage',
                $manager->settings[$setting_id]['transport'],
                "Setting \"{$setting_id}\" must use 'postMessage' transport."
            );
        }
    }

    // =========================================================================
    // AC6 — Adding a step via filter creates a Customizer control automatically
    // =========================================================================

    /** @test */
    public function customizer_auto_registers_control_for_filter_added_non_alias_step(): void
    {
        // Register a filter that adds a new preset step (non-alias)
        add_filter('dynamo_layout_width_presets', function (array $presets): array {
            $presets['feature'] = ['label' => 'Feature', 'default' => '900px'];
            return $presets;
        });

        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_layout_width_feature',
            $control_ids,
            'Customizer must auto-register a control for a non-alias preset step added via the filter.'
        );
    }

    /** @test */
    public function customizer_filter_added_step_control_is_in_width_scale_section(): void
    {
        add_filter('dynamo_layout_width_presets', function (array $presets): array {
            $presets['feature'] = ['label' => 'Feature', 'default' => '900px'];
            return $presets;
        });

        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $feature_controls = array_filter(
            $manager->controls,
            fn($c) => $c->id === 'dynamo_layout_width_feature'
        );

        $this->assertCount(
            1,
            $feature_controls,
            'Exactly one Customizer control must be added for the filter-added "feature" step.'
        );

        $control = array_values($feature_controls)[0];
        $this->assertSame(
            'dynamo_layout_width_scale',
            $control->args['section'] ?? null,
            'The auto-registered control for the filter-added step must be in the "dynamo_layout_width_scale" section.'
        );
    }

    // =========================================================================
    // AC7 — Alias steps resolve dynamically when underlying role token changes
    // =========================================================================

    /** @test */
    public function resolve_alias_default_follows_changed_content_width(): void
    {
        // Override the underlying content-width token
        $GLOBALS['wp_theme_mods']['dynamo_layout_content_width'] = '800px';

        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-default');

        $this->assertSame(
            '800px',
            $resolved,
            'resolve_alias("layout-width-default") must return the updated value of "layout-content-width" (800px).'
        );
    }

    /** @test */
    public function resolve_alias_container_follows_changed_container_max_width(): void
    {
        // Override the underlying container-max-width token
        $GLOBALS['wp_theme_mods']['dynamo_layout_container_max_width'] = '1400px';

        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('layout-width-container');

        $this->assertSame(
            '1400px',
            $resolved,
            'resolve_alias("layout-width-container") must return the updated value of "layout-container-max-width" (1400px).'
        );
    }

    /** @test */
    public function resolve_alias_default_does_not_return_stale_value_after_role_token_changes(): void
    {
        $registry_before = new Dynamo_Token_Registry();
        $original        = $registry_before->resolve_alias('layout-width-default');

        // Change the role token
        $GLOBALS['wp_theme_mods']['dynamo_layout_content_width'] = '999px';

        $registry_after = new Dynamo_Token_Registry();
        $updated        = $registry_after->resolve_alias('layout-width-default');

        $this->assertNotSame(
            $original,
            $updated,
            'resolve_alias("layout-width-default") must reflect live changes to the role token, not cache stale values.'
        );
        $this->assertSame('999px', $updated);
    }
}
