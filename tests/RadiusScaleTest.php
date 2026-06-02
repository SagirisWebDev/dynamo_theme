<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #37 — PRD v1.3.0 Slice 4: Complete Radius Scale + Customizer subsection
 *
 * PHPUnit tests covering:
 *   AC1 — Token registry registers all six radius steps with correct defaults.
 *   AC2 — Dynamo_Token_Registry::resolve_alias() resolves borders-radius-default;
 *          is_alias('borders-radius-default') returns true.
 *   AC3 — borders-radius-default reflects live changes to the borders-radius role token;
 *          the CSS generator emits --dynamo-borders-radius-default with the correct value.
 *   AC4 — dynamo_border_radius_presets() returns exactly 6 entries with the correct shape.
 *   AC6 — Customizer "Radius Scale" subsection appears inside the dynamo_borders_shadows panel;
 *          controls for none/sm/lg/xl/pill are registered; no control for 'default' (alias).
 *   AC7 — Custom step added via filter gets a Customizer control automatically.
 */
class RadiusScaleTest extends TestCase
{
    use MakesCustomizer;

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']         = [];
        $GLOBALS['wp_doing_it_wrong'] = [];
        $GLOBALS['wp_theme_mods']     = [];
    }

    // =========================================================================
    // AC1 — Token Registry registers all six radius steps
    // =========================================================================

    /** @test */
    public function token_registry_registers_borders_radius_none(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'borders-radius-none',
            $registry->all(),
            'Token registry must include the key "borders-radius-none".'
        );
    }

    /** @test */
    public function token_registry_borders_radius_none_default_is_0(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '0',
            $registry->get('borders-radius-none'),
            'Default value for "borders-radius-none" must be "0".'
        );
    }

    /** @test */
    public function token_registry_registers_borders_radius_sm(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'borders-radius-sm',
            $registry->all(),
            'Token registry must include the key "borders-radius-sm".'
        );
    }

    /** @test */
    public function token_registry_borders_radius_sm_default_is_0_25rem(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '0.25rem',
            $registry->get('borders-radius-sm'),
            'Default value for "borders-radius-sm" must be "0.25rem".'
        );
    }

    /** @test */
    public function token_registry_registers_borders_radius_default(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'borders-radius-default',
            $registry->all(),
            'Token registry must include the key "borders-radius-default".'
        );
    }

    /** @test */
    public function token_registry_registers_borders_radius_xl(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'borders-radius-xl',
            $registry->all(),
            'Token registry must include the key "borders-radius-xl".'
        );
    }

    /** @test */
    public function token_registry_borders_radius_xl_default_is_0_75rem(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '0.75rem',
            $registry->get('borders-radius-xl'),
            'Default value for "borders-radius-xl" must be "0.75rem".'
        );
    }

    /** @test */
    public function token_registry_registers_borders_radius_pill(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertArrayHasKey(
            'borders-radius-pill',
            $registry->all(),
            'Token registry must include the key "borders-radius-pill".'
        );
    }

    /** @test */
    public function token_registry_borders_radius_pill_default_is_9999px(): void
    {
        $registry = new Dynamo_Token_Registry();

        $this->assertSame(
            '9999px',
            $registry->get('borders-radius-pill'),
            'Default value for "borders-radius-pill" must be "9999px".'
        );
    }

    /** @test */
    public function token_registry_all_six_radius_steps_are_present(): void
    {
        $registry = new Dynamo_Token_Registry();
        $all      = $registry->all();

        $expected = [
            'borders-radius-none',
            'borders-radius-sm',
            'borders-radius-default',
            'borders-radius-lg',
            'borders-radius-xl',
            'borders-radius-pill',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $all, "Missing token key \"{$key}\" from the registry.");
        }
    }

    // =========================================================================
    // AC2 — resolve_alias() and is_alias() for borders-radius-default
    // =========================================================================

    /** @test */
    public function is_alias_returns_true_for_borders_radius_default(): void
    {
        $this->assertTrue(
            Dynamo_Token_Registry::is_alias('borders-radius-default'),
            'Dynamo_Token_Registry::is_alias("borders-radius-default") must return true.'
        );
    }

    /** @test */
    public function is_alias_returns_false_for_borders_radius_lg(): void
    {
        $this->assertFalse(
            Dynamo_Token_Registry::is_alias('borders-radius-lg'),
            'Dynamo_Token_Registry::is_alias("borders-radius-lg") must return false (not an alias).'
        );
    }

    /** @test */
    public function resolve_alias_returns_borders_radius_role_token_value_for_borders_radius_default(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('borders-radius-default');
        $expected = $registry->get('borders-radius');

        $this->assertNotNull(
            $resolved,
            'resolve_alias("borders-radius-default") must not return null.'
        );
        $this->assertSame(
            $expected,
            $resolved,
            'resolve_alias("borders-radius-default") must resolve to the value of the "borders-radius" role token.'
        );
    }

    /** @test */
    public function resolve_alias_borders_radius_default_returns_0_375rem_at_factory_defaults(): void
    {
        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('borders-radius-default');

        $this->assertSame(
            '0.375rem',
            $resolved,
            'resolve_alias("borders-radius-default") must equal the factory default of "borders-radius" (0.375rem).'
        );
    }

    // =========================================================================
    // AC3 — borders-radius-default is live (follows role token changes)
    // =========================================================================

    /** @test */
    public function resolve_alias_default_follows_changed_borders_radius_role_token(): void
    {
        $GLOBALS['wp_theme_mods']['dynamo_borders_radius'] = '1rem';

        $registry = new Dynamo_Token_Registry();
        $resolved = $registry->resolve_alias('borders-radius-default');

        $this->assertSame(
            '1rem',
            $resolved,
            'resolve_alias("borders-radius-default") must return the updated value of "borders-radius" (1rem).'
        );
    }

    /** @test */
    public function resolve_alias_default_does_not_return_stale_value_after_role_token_changes(): void
    {
        $registry_before = new Dynamo_Token_Registry();
        $original        = $registry_before->resolve_alias('borders-radius-default');

        $GLOBALS['wp_theme_mods']['dynamo_borders_radius'] = '2rem';

        $registry_after = new Dynamo_Token_Registry();
        $updated        = $registry_after->resolve_alias('borders-radius-default');

        $this->assertNotSame(
            $original,
            $updated,
            'resolve_alias("borders-radius-default") must reflect live changes to the role token, not cache stale values.'
        );
        $this->assertSame('2rem', $updated);
    }

    /** @test */
    public function css_generator_emits_borders_radius_default_custom_property(): void
    {
        $registry  = new Dynamo_Token_Registry();
        $fonts     = new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
        $generator = new Dynamo_CSS_Generator($registry, $fonts);
        $css       = $generator->generate();

        $this->assertStringContainsString(
            '--dynamo-borders-radius-default',
            $css,
            'Generated CSS must contain the custom property --dynamo-borders-radius-default.'
        );
    }

    /** @test */
    public function css_generator_emits_borders_radius_default_with_role_token_value(): void
    {
        $GLOBALS['wp_theme_mods']['dynamo_borders_radius'] = '0.5rem';

        $registry  = new Dynamo_Token_Registry();
        $fonts     = new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
        $generator = new Dynamo_CSS_Generator($registry, $fonts);
        $css       = $generator->generate();

        $this->assertStringContainsString(
            '--dynamo-borders-radius-default: 0.5rem;',
            $css,
            'Generated CSS must emit "--dynamo-borders-radius-default: 0.5rem;" when borders-radius role token is 0.5rem.'
        );
    }

    // =========================================================================
    // AC4 — dynamo_border_radius_presets() returns full canonical 6-step list
    // =========================================================================

    /** @test */
    public function border_radius_presets_has_exactly_six_steps(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertCount(
            6,
            $presets,
            'dynamo_border_radius_presets() must return exactly 6 entries (none, sm, default, lg, xl, pill).'
        );
    }

    /** @test */
    public function border_radius_presets_returns_all_six_keys(): void
    {
        $presets      = dynamo_border_radius_presets();
        $expected_keys = ['none', 'sm', 'default', 'lg', 'xl', 'pill'];

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $presets,
                "dynamo_border_radius_presets() must contain the \"{$key}\" key."
            );
        }
    }

    /** @test */
    public function border_radius_presets_none_has_label_None(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'None',
            $presets['none']['label'],
            'The "none" preset label must be "None".'
        );
    }

    /** @test */
    public function border_radius_presets_none_has_default_of_0(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            '0',
            $presets['none']['default'],
            'The "none" preset default must be "0".'
        );
    }

    /** @test */
    public function border_radius_presets_sm_has_label_Small(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'Small',
            $presets['sm']['label'],
            'The "sm" preset label must be "Small".'
        );
    }

    /** @test */
    public function border_radius_presets_sm_has_default_of_0_25rem(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            '0.25rem',
            $presets['sm']['default'],
            'The "sm" preset default must be "0.25rem".'
        );
    }

    /** @test */
    public function border_radius_presets_default_has_label_Default(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'Default',
            $presets['default']['label'],
            'The "default" preset label must be "Default".'
        );
    }

    /** @test */
    public function border_radius_presets_xl_has_label_X_Large(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'X-Large',
            $presets['xl']['label'],
            'The "xl" preset label must be "X-Large".'
        );
    }

    /** @test */
    public function border_radius_presets_xl_has_default_of_0_75rem(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            '0.75rem',
            $presets['xl']['default'],
            'The "xl" preset default must be "0.75rem".'
        );
    }

    /** @test */
    public function border_radius_presets_pill_has_label_Pill(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            'Pill',
            $presets['pill']['label'],
            'The "pill" preset label must be "Pill".'
        );
    }

    /** @test */
    public function border_radius_presets_pill_has_default_of_9999px(): void
    {
        $presets = dynamo_border_radius_presets();

        $this->assertSame(
            '9999px',
            $presets['pill']['default'],
            'The "pill" preset default must be "9999px".'
        );
    }

    /** @test */
    public function border_radius_presets_each_entry_has_label_and_default_keys(): void
    {
        $presets = dynamo_border_radius_presets();

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
    // AC6 — Customizer "Radius Scale" subsection inside dynamo_borders_shadows
    // =========================================================================

    /** @test */
    public function customizer_register_adds_radius_scale_section(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertArrayHasKey(
            'dynamo_radius_scale',
            $manager->sections,
            'Customizer must add a "dynamo_radius_scale" section.'
        );
    }

    /** @test */
    public function customizer_radius_scale_section_belongs_to_borders_shadows_panel(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $this->assertSame(
            'dynamo_borders_shadows',
            $manager->sections['dynamo_radius_scale']['panel'],
            'The "dynamo_radius_scale" section must belong to the "dynamo_borders_shadows" panel.'
        );
    }

    /** @test */
    public function customizer_radius_scale_has_control_for_none(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_none',
            $control_ids,
            'Customizer must add a control with id "dynamo_borders_radius_none".'
        );
    }

    /** @test */
    public function customizer_radius_scale_has_control_for_sm(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_sm',
            $control_ids,
            'Customizer must add a control with id "dynamo_borders_radius_sm".'
        );
    }

    /** @test */
    public function customizer_radius_scale_has_control_for_lg(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_lg',
            $control_ids,
            'Customizer must add a control with id "dynamo_borders_radius_lg".'
        );
    }

    /** @test */
    public function customizer_radius_scale_has_control_for_xl(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_xl',
            $control_ids,
            'Customizer must add a control with id "dynamo_borders_radius_xl".'
        );
    }

    /** @test */
    public function customizer_radius_scale_has_control_for_pill(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_pill',
            $control_ids,
            'Customizer must add a control with id "dynamo_borders_radius_pill".'
        );
    }

    /** @test */
    public function customizer_radius_scale_does_not_add_control_for_default_alias(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertNotContains(
            'dynamo_borders_radius_default',
            $control_ids,
            'Customizer must NOT add a control for alias step "default" — it inherits from "borders-radius".'
        );
    }

    /** @test */
    public function customizer_radius_scale_controls_are_in_radius_scale_section(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $expected_control_ids = [
            'dynamo_borders_radius_none',
            'dynamo_borders_radius_sm',
            'dynamo_borders_radius_lg',
            'dynamo_borders_radius_xl',
            'dynamo_borders_radius_pill',
        ];

        $radius_scale_controls = array_filter(
            $manager->controls,
            fn($c) => in_array($c->id, $expected_control_ids, true)
        );

        $this->assertCount(
            5,
            $radius_scale_controls,
            'Customizer must register exactly 5 controls in the radius scale section (none, sm, lg, xl, pill).'
        );

        foreach ($radius_scale_controls as $control) {
            $this->assertSame(
                'dynamo_radius_scale',
                $control->args['section'] ?? null,
                "Control {$control->id} must be assigned to the 'dynamo_radius_scale' section."
            );
        }
    }

    /** @test */
    public function customizer_radius_scale_settings_use_postmessage_transport(): void
    {
        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        foreach (['dynamo_borders_radius_none', 'dynamo_borders_radius_sm', 'dynamo_borders_radius_lg', 'dynamo_borders_radius_xl', 'dynamo_borders_radius_pill'] as $setting_id) {
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
    // AC7 — Custom step added via filter gets a Customizer control automatically
    // =========================================================================

    /** @test */
    public function customizer_auto_registers_control_for_filter_added_non_alias_step(): void
    {
        add_filter('dynamo_border_radius_presets', function (array $presets): array {
            $presets['jumbo'] = ['label' => 'Jumbo', 'default' => '2rem'];
            return $presets;
        });

        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $control_ids = array_map(fn($c) => $c->id, $manager->controls);

        $this->assertContains(
            'dynamo_borders_radius_jumbo',
            $control_ids,
            'Customizer must auto-register a control for a non-alias preset step added via the filter.'
        );
    }

    /** @test */
    public function customizer_filter_added_step_control_is_in_radius_scale_section(): void
    {
        add_filter('dynamo_border_radius_presets', function (array $presets): array {
            $presets['jumbo'] = ['label' => 'Jumbo', 'default' => '2rem'];
            return $presets;
        });

        $manager    = new FakeCustomizeManager();
        $customizer = $this->make_customizer();
        $customizer->register($manager);

        $jumbo_controls = array_filter(
            $manager->controls,
            fn($c) => $c->id === 'dynamo_borders_radius_jumbo'
        );

        $this->assertCount(
            1,
            $jumbo_controls,
            'Exactly one Customizer control must be added for the filter-added "jumbo" step.'
        );

        $control = array_values($jumbo_controls)[0];
        $this->assertSame(
            'dynamo_radius_scale',
            $control->args['section'] ?? null,
            'The auto-registered control for the filter-added step must be in the "dynamo_radius_scale" section.'
        );
    }
}
