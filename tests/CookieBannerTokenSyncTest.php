<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * TDD Red Phase — Issue #27: Banner Token Sync
 *
 * These tests are written BEFORE the implementation exists and are expected
 * to fail until the production code satisfies all acceptance criteria.
 *
 * Acceptance criteria covered:
 *
 *  AC1 — Both drivers implement register_palette_sync_hooks() and register
 *         the correct plugin-specific WP hook:
 *           • Complianz: cmplz_banner_css
 *           • Borlabs:   borlabsCookie/styleBuilder/modifyCss
 *
 *  AC2 — The default token map injects exactly five CSS custom properties:
 *           --cookie-primary, --cookie-background, --cookie-text,
 *           --cookie-link, --cookie-font-family
 *         Each must be sourced from the corresponding Token Registry value.
 *
 *  AC3 — A dynamo_cookie_banner_tokens filter allows developers to add,
 *         remove, or remap entries in the token map.
 *
 *  AC4 — Covered implicitly: the drivers read live values from
 *         Dynamo_Token_Registry on every hook invocation, so a Customizer
 *         change propagates on the next page load without additional tests.
 *
 *  AC5 — Unit tests assert that each driver registers its hook (AC1) and
 *         that the default token map produces correct CSS custom property
 *         output (AC2). Both are covered here.
 */
class CookieBannerTokenSyncTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Test lifecycle
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_theme_supports']      = [];
        $GLOBALS['wp_removed_actions']     = [];
        $GLOBALS['wp_doing_it_wrong']      = [];
        $GLOBALS['wp_options']             = [];
        $GLOBALS['wp_update_option_calls'] = [];
        $GLOBALS['wpdb_update_calls']      = [];

        $this->loadCookieFiles();
    }

    protected function tearDown(): void
    {
        // Remove any filters added during a test so they do not leak into
        // subsequent test methods.
        unset($GLOBALS['wp_filter']['dynamo_cookie_banner_tokens']);
        unset($GLOBALS['wp_filter']['dynamo_token_defaults']);
        unset($GLOBALS['wp_filter']['dynamo_complianz_colorpalette']);
    }

    // -----------------------------------------------------------------------
    // AC1 — Complianz driver registers customize_save_after (NOT cmplz_banner_css)
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_driver_register_palette_sync_hooks_adds_customize_save_after_action(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_palette_sync_hooks();

        $this->assertArrayHasKey(
            'customize_save_after',
            $GLOBALS['wp_filter'],
            'Complianz driver must register a callback on the customize_save_after hook.'
        );

        $registeredCallbacks = array_merge(...array_values($GLOBALS['wp_filter']['customize_save_after']));
        $this->assertNotEmpty(
            $registeredCallbacks,
            'At least one callback must be registered on customize_save_after.'
        );
    }

    /** @test */
    public function complianz_driver_does_not_register_cmplz_banner_css_hook(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_palette_sync_hooks();

        // cmplz_banner_css is a do_action (not apply_filters) and was the
        // root cause of the broken sync. The new driver must not use it.
        $this->assertArrayNotHasKey(
            'cmplz_banner_css',
            $GLOBALS['wp_filter'],
            'Complianz driver must NOT register on cmplz_banner_css (it is a do_action, not a filter).'
        );
    }

    // -----------------------------------------------------------------------
    // AC1 — Borlabs driver registers the correct hook
    // -----------------------------------------------------------------------

    /** @test */
    public function borlabs_driver_register_palette_sync_hooks_adds_borlabs_style_builder_action(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $this->assertArrayHasKey(
            'borlabsCookie/styleBuilder/modifyCss',
            $GLOBALS['wp_filter'],
            'Borlabs driver must register a callback on the borlabsCookie/styleBuilder/modifyCss hook.'
        );

        $registeredCallbacks = array_merge(...array_values($GLOBALS['wp_filter']['borlabsCookie/styleBuilder/modifyCss']));
        $this->assertNotEmpty(
            $registeredCallbacks,
            'At least one callback must be registered on borlabsCookie/styleBuilder/modifyCss.'
        );
    }

    /** @test */
    public function borlabs_driver_does_not_register_wrong_hook(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        // The stub registers borlabs_cookie_consent_updated — that must be gone.
        $this->assertArrayNotHasKey(
            'borlabs_cookie_consent_updated',
            $GLOBALS['wp_filter'],
            'Borlabs driver must NOT register on borlabs_cookie_consent_updated (old stub hook).'
        );
    }

    // -----------------------------------------------------------------------
    // AC2 — build_palette() returns correct colorpalette arrays per field
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_build_palette_colorpalette_background_maps_color_and_border(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_background', $palette);
        $this->assertSame(
            $registry->get('colors-background'),
            $palette['colorpalette_background']['color'] ?? null,
            "colorpalette_background.color must map to 'colors-background'."
        );
        $this->assertSame(
            $registry->get('borders-color'),
            $palette['colorpalette_background']['border'] ?? null,
            "colorpalette_background.border must map to 'borders-color'."
        );
    }

    /** @test */
    public function complianz_build_palette_colorpalette_text_maps_color_and_hyperlink(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_text', $palette);
        $this->assertSame(
            $registry->get('colors-text'),
            $palette['colorpalette_text']['color'] ?? null,
            "colorpalette_text.color must map to 'colors-text'."
        );
        $this->assertSame(
            $registry->get('colors-link'),
            $palette['colorpalette_text']['hyperlink'] ?? null,
            "colorpalette_text.hyperlink must map to 'colors-link'."
        );
    }

    /** @test */
    public function complianz_build_palette_colorpalette_toggles_maps_background_bullet_inactive(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_toggles', $palette);
        $this->assertSame(
            $registry->get('colors-primary'),
            $palette['colorpalette_toggles']['background'] ?? null,
            "colorpalette_toggles.background must map to 'colors-primary'."
        );
        $this->assertSame(
            $registry->get('colors-background'),
            $palette['colorpalette_toggles']['bullet'] ?? null,
            "colorpalette_toggles.bullet must map to 'colors-background'."
        );
        $this->assertSame(
            $registry->get('colors-accent'),
            $palette['colorpalette_toggles']['inactive'] ?? null,
            "colorpalette_toggles.inactive must map to 'colors-accent'."
        );
    }

    /** @test */
    public function complianz_build_palette_colorpalette_button_accept_maps_background_border_text(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_button_accept', $palette);
        $this->assertSame(
            $registry->get('colors-primary'),
            $palette['colorpalette_button_accept']['background'] ?? null,
            "colorpalette_button_accept.background must map to 'colors-primary'."
        );
        $this->assertSame(
            $registry->get('colors-primary'),
            $palette['colorpalette_button_accept']['border'] ?? null,
            "colorpalette_button_accept.border must map to 'colors-primary'."
        );
        $this->assertSame(
            $registry->get('colors-background'),
            $palette['colorpalette_button_accept']['text'] ?? null,
            "colorpalette_button_accept.text must map to 'colors-background'."
        );
    }

    /** @test */
    public function complianz_build_palette_colorpalette_button_deny_maps_background_border_text(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_button_deny', $palette);
        $this->assertSame(
            $registry->get('colors-background'),
            $palette['colorpalette_button_deny']['background'] ?? null,
            "colorpalette_button_deny.background must map to 'colors-background'."
        );
        $this->assertSame(
            $registry->get('colors-secondary'),
            $palette['colorpalette_button_deny']['border'] ?? null,
            "colorpalette_button_deny.border must map to 'colors-secondary'."
        );
        $this->assertSame(
            $registry->get('colors-text'),
            $palette['colorpalette_button_deny']['text'] ?? null,
            "colorpalette_button_deny.text must map to 'colors-text'."
        );
    }

    /** @test */
    public function complianz_build_palette_colorpalette_button_settings_maps_background_border_text(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey('colorpalette_button_settings', $palette);
        $this->assertSame(
            $registry->get('colors-background'),
            $palette['colorpalette_button_settings']['background'] ?? null,
            "colorpalette_button_settings.background must map to 'colors-background'."
        );
        $this->assertSame(
            $registry->get('colors-secondary'),
            $palette['colorpalette_button_settings']['border'] ?? null,
            "colorpalette_button_settings.border must map to 'colors-secondary'."
        );
        $this->assertSame(
            $registry->get('colors-text'),
            $palette['colorpalette_button_settings']['text'] ?? null,
            "colorpalette_button_settings.text must map to 'colors-text'."
        );
    }

    // -----------------------------------------------------------------------
    // AC2 — Borlabs callback injects all 5 CSS custom properties
    // -----------------------------------------------------------------------

    /** @test */
    public function borlabs_callback_appends_cookie_primary_from_token_registry(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('colors-primary');

        $this->assertStringContainsString('--cookie-primary', $css);
        $this->assertStringContainsString(
            $expected,
            $css,
            "--cookie-primary must equal the Token Registry value for 'colors-primary' ({$expected})."
        );
    }

    /** @test */
    public function borlabs_callback_appends_cookie_background_from_token_registry(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('colors-background');

        $this->assertStringContainsString('--cookie-background', $css);
        $this->assertStringContainsString($expected, $css);
    }

    /** @test */
    public function borlabs_callback_appends_cookie_text_from_token_registry(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('colors-text');

        $this->assertStringContainsString('--cookie-text', $css);
        $this->assertStringContainsString($expected, $css);
    }

    /** @test */
    public function borlabs_callback_appends_cookie_link_from_token_registry(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('colors-link');

        $this->assertStringContainsString('--cookie-link', $css);
        $this->assertStringContainsString($expected, $css);
    }

    /** @test */
    public function borlabs_callback_appends_cookie_font_family_from_token_registry(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('typography-body-font-family');

        $this->assertStringContainsString('--cookie-font-family', $css);
        $this->assertStringContainsString($expected, $css);
    }

    /** @test */
    public function borlabs_callback_preserves_existing_css_and_appends_properties(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $existing = '.BorlabsCookie { color: blue; }';
        $css       = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', $existing);

        $this->assertStringContainsString(
            $existing,
            $css,
            'Borlabs callback must preserve existing CSS passed into the filter.'
        );
    }

    // -----------------------------------------------------------------------
    // AC3 — dynamo_complianz_colorpalette filter: add, remove, remap entries
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_build_palette_respects_filter_adding_new_palette_field(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        add_filter('dynamo_complianz_colorpalette', static function (array $map): array {
            $map['colorpalette_custom'] = [
                'color' => 'colors-accent',
            ];
            return $map;
        });

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayHasKey(
            'colorpalette_custom',
            $palette,
            'build_palette() must include new palette fields added via dynamo_complianz_colorpalette filter.'
        );
        $this->assertSame(
            $registry->get('colors-accent'),
            $palette['colorpalette_custom']['color'] ?? null,
            "colorpalette_custom.color must equal the Token Registry value for 'colors-accent'."
        );
    }

    /** @test */
    public function complianz_build_palette_respects_filter_removing_a_palette_field(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        add_filter('dynamo_complianz_colorpalette', static function (array $map): array {
            unset($map['colorpalette_toggles']);
            return $map;
        });

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertArrayNotHasKey(
            'colorpalette_toggles',
            $palette,
            'build_palette() must omit palette fields removed via dynamo_complianz_colorpalette filter.'
        );
    }

    /** @test */
    public function complianz_build_palette_respects_filter_remapping_a_subkey_to_different_token(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // Remap colorpalette_toggles.background from colors-primary to colors-secondary.
        add_filter('dynamo_complianz_colorpalette', static function (array $map): array {
            $map['colorpalette_toggles']['background'] = 'colors-secondary';
            return $map;
        });

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        $this->assertSame(
            $registry->get('colors-secondary'),
            $palette['colorpalette_toggles']['background'] ?? null,
            "colorpalette_toggles.background must equal the remapped Token Registry value for 'colors-secondary'."
        );
        $this->assertNotSame(
            $registry->get('colors-primary'),
            $palette['colorpalette_toggles']['background'] ?? null,
            "colorpalette_toggles.background must NOT equal the original 'colors-primary' value after remapping."
        );
    }

    /** @test */
    public function borlabs_callback_respects_filter_adding_new_token(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        add_filter('dynamo_cookie_banner_tokens', static function (array $map): array {
            $map['--cookie-accent'] = 'colors-accent';
            return $map;
        });

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $registry = new Dynamo_Token_Registry();
        $expected  = $registry->get('colors-accent');

        $this->assertStringContainsString('--cookie-accent', $css);
        $this->assertStringContainsString($expected, $css);
    }

    /** @test */
    public function borlabs_callback_respects_filter_removing_a_token(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        add_filter('dynamo_cookie_banner_tokens', static function (array $map): array {
            unset($map['--cookie-font-family']);
            return $map;
        });

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $this->assertStringNotContainsString(
            '--cookie-font-family',
            $css,
            'Borlabs callback must NOT include --cookie-font-family when removed via the filter.'
        );
    }

    // -----------------------------------------------------------------------
    // AC4 — Changing Token Registry value propagates to the CSS output
    // (covered implicitly by live registry reads; this test makes it explicit)
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_build_palette_reflects_customizer_change_to_primary_color(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // Simulate a Customizer change by overriding the token via the WP filter.
        add_filter('dynamo_token_defaults', static function (array $defaults): array {
            $defaults['colors-primary'] = '#ff0000';
            return $defaults;
        });

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);

        // colors-primary feeds colorpalette_toggles.background and the accept button.
        $this->assertSame(
            '#ff0000',
            $palette['colorpalette_toggles']['background'] ?? null,
            'colorpalette_toggles.background must reflect the updated colors-primary value (#ff0000).'
        );
        $this->assertSame(
            '#ff0000',
            $palette['colorpalette_button_accept']['background'] ?? null,
            'colorpalette_button_accept.background must reflect the updated colors-primary value (#ff0000).'
        );
        $this->assertSame(
            '#ff0000',
            $palette['colorpalette_button_accept']['border'] ?? null,
            'colorpalette_button_accept.border must reflect the updated colors-primary value (#ff0000).'
        );
    }

    /** @test */
    public function borlabs_callback_reflects_customizer_change_to_primary_color(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        add_filter('dynamo_token_defaults', static function (array $defaults): array {
            $defaults['colors-primary'] = '#00ff00';
            return $defaults;
        });

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_palette_sync_hooks();

        $css = $this->invokeFirstCallbackOnFilter('borlabsCookie/styleBuilder/modifyCss', '');

        $this->assertStringContainsString(
            '#00ff00',
            $css,
            '--cookie-primary must reflect the updated Customizer value (#00ff00) read from Token Registry.'
        );
    }

    // -----------------------------------------------------------------------
    // AC5 — Hash guard: Complianz colors not overwritten on non-color saves
    // -----------------------------------------------------------------------

    /** @test */
    public function customize_save_after_skips_db_write_when_palette_hash_unchanged(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver   = new Dynamo_Cookie_Driver_Complianz();
        $registry = new Dynamo_Token_Registry();
        $palette  = $driver->build_palette($registry);
        $hash     = md5(serialize($palette));

        // Pre-seed a matching hash so this save looks like "nothing changed".
        $GLOBALS['wp_options']['dynamo_complianz_palette_hash'] = $hash;

        $driver->register_palette_sync_hooks();
        $this->invokeCustomizeSaveAfterCallback();

        // No update_option calls should have been made (early return path).
        $this->assertEmpty(
            $GLOBALS['wp_update_option_calls'],
            'When the palette hash is unchanged, customize_save_after must not call update_option (Complianz colors must be left alone).'
        );

        // No DB rows should have been written.
        $this->assertEmpty(
            $GLOBALS['wpdb_update_calls'],
            'When the palette hash is unchanged, customize_save_after must not write to wp_cmplz_cookiebanners.'
        );
    }

    /** @test */
    public function customize_save_after_writes_db_and_stores_hash_when_palette_changes(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // Stale hash → this save is treated as a color-token change.
        $GLOBALS['wp_options']['dynamo_complianz_palette_hash'] = 'stale-hash';

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_palette_sync_hooks();
        $this->invokeCustomizeSaveAfterCallback();

        $stored = $GLOBALS['wp_options']['dynamo_complianz_palette_hash'] ?? '';

        $this->assertNotSame(
            'stale-hash',
            $stored,
            'After a palette change, customize_save_after must overwrite the stale hash.'
        );
        $this->assertNotEmpty(
            $stored,
            'After a palette change, a non-empty hash must be persisted via update_option.'
        );
        $this->assertNotEmpty(
            $GLOBALS['wpdb_update_calls'],
            'After a palette change, customize_save_after must write to wp_cmplz_cookiebanners.'
        );
    }

    // -----------------------------------------------------------------------
    // AC5 — admin_init baseline: hash seeded without writing to Complianz DB
    // -----------------------------------------------------------------------

    /** @test */
    public function admin_init_stores_baseline_hash_when_no_hash_exists(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // No hash stored yet (simulates first admin page load after deployment).
        unset($GLOBALS['wp_options']['dynamo_complianz_palette_hash']);

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_palette_sync_hooks();
        $this->invokeAdminInitCallback();

        $stored = $GLOBALS['wp_options']['dynamo_complianz_palette_hash'] ?? false;

        $this->assertNotFalse(
            $stored,
            'admin_init must store the palette hash when none exists yet.'
        );
        $this->assertNotEmpty(
            $stored,
            'admin_init must store a non-empty hash string.'
        );
        // Critically: no DB rows written — only the option is set.
        $this->assertEmpty(
            $GLOBALS['wpdb_update_calls'],
            'admin_init must NOT write to wp_cmplz_cookiebanners — it only seeds the hash.'
        );
    }

    /** @test */
    public function admin_init_does_not_overwrite_existing_hash(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $existing = 'previously-stored-hash';
        $GLOBALS['wp_options']['dynamo_complianz_palette_hash'] = $existing;

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_palette_sync_hooks();
        $this->invokeAdminInitCallback();

        $this->assertSame(
            $existing,
            $GLOBALS['wp_options']['dynamo_complianz_palette_hash'],
            'admin_init must not overwrite a hash that already exists.'
        );
        $this->assertEmpty(
            $GLOBALS['wpdb_update_calls'],
            'admin_init must not write to Complianz DB when a hash already exists.'
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Soft-load the cookie driver files. During the red phase these files
     * exist but contain stubs; tests will fail at the assertions level rather
     * than with a fatal require error.
     */
    private function loadCookieFiles(): void
    {
        $files = [
            DYNAMO_PATH . 'includes/cookie/interface-dynamo-cookie-driver.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-complianz.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-borlabs.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-integration.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Assert a class exists, producing a clear failure message if it does not.
     */
    private function assertClassExists(string $className): void
    {
        $this->assertTrue(
            class_exists($className),
            "Class {$className} must exist. Ensure the cookie driver files are loaded."
        );
    }

    /**
     * Fire every callback registered on admin_init, in priority order.
     */
    private function invokeAdminInitCallback(): void
    {
        if (empty($GLOBALS['wp_filter']['admin_init'])) {
            return;
        }
        ksort($GLOBALS['wp_filter']['admin_init']);
        foreach ($GLOBALS['wp_filter']['admin_init'] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }

    /**
     * Fire every callback registered on customize_save_after, in priority order.
     * The hook receives no arguments (it is an action, not a filter).
     */
    private function invokeCustomizeSaveAfterCallback(): void
    {
        if (empty($GLOBALS['wp_filter']['customize_save_after'])) {
            return;
        }
        ksort($GLOBALS['wp_filter']['customize_save_after']);
        foreach ($GLOBALS['wp_filter']['customize_save_after'] as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }

    /**
     * Invoke every registered callback for $hookName in priority order,
     * starting with $initialValue, and return the final filtered value.
     * This mirrors how apply_filters() works in the stub bootstrap.
     */
    private function invokeFirstCallbackOnFilter(string $hookName, string $initialValue): string
    {
        $this->assertArrayHasKey(
            $hookName,
            $GLOBALS['wp_filter'],
            "Expected hook '{$hookName}' to be registered, but it was not found in \$wp_filter."
        );

        $value = $initialValue;
        ksort($GLOBALS['wp_filter'][$hookName]);
        foreach ($GLOBALS['wp_filter'][$hookName] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value);
            }
        }
        return (string) $value;
    }
}
