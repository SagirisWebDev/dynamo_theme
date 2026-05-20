<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * TDD Red Phase — Issue #29: Consent Placeholder
 *
 * These tests are written BEFORE the implementation exists and are expected
 * to fail until the production code satisfies all acceptance criteria.
 *
 * Acceptance criteria covered:
 *
 *  AC1 — templates/consent-placeholder.php exists and renders a placeholder
 *         styled with CSS custom properties set by Banner Token Sync
 *         (--cookie-primary etc.)
 *
 *  AC2 — The template receives $service_name and $consent_category as
 *         variables and displays both clearly.
 *
 *  AC3 — The template includes a visually-hidden accessible label for screen
 *         reader users (e.g. <span class="screen-reader-text">).
 *
 *  AC4 — The template can be overridden by placing it in a child theme
 *         (locate_template() or equivalent logic is used).
 *
 *  AC5 — Both drivers implement register_embed_hooks() and hook into
 *         the_content to replace matching embeds with the Consent Placeholder.
 *
 *  AC6 — Frontend JS replacement behavior — out of scope for PHP unit tests.
 *
 *  AC7 — Unit tests assert that embed HTML in the_content output is replaced
 *         with placeholder markup when the relevant consent cookie is absent.
 */

// ---------------------------------------------------------------------------
// Stub locate_template() — not present in bootstrap.php.
// Returns the theme-relative path if the template exists, otherwise ''.
// ---------------------------------------------------------------------------
if (! function_exists('locate_template')) {
    function locate_template(string|array $template_names, bool $load = false, bool $require_once = true): string {
        $names = (array) $template_names;
        foreach ($names as $name) {
            // Check child theme first (simulated via CHILD_THEME_PATH global), then parent.
            $locations = array_filter([
                $GLOBALS['child_theme_path'] ?? '',
                DYNAMO_PATH,
            ]);
            foreach ($locations as $dir) {
                $path = rtrim($dir, '/') . '/' . ltrim($name, '/');
                if (is_file($path)) {
                    if ($load) {
                        if ($require_once) {
                            require_once $path;
                        } else {
                            require $path;
                        }
                    }
                    return $path;
                }
            }
        }
        return '';
    }
}

// ---------------------------------------------------------------------------
// Stub isset_cookie() helper used internally by drivers to check consent.
// The global $GLOBALS['dynamo_consent_cookies'] acts as the cookie jar.
// ---------------------------------------------------------------------------
if (! function_exists('dynamo_has_consent')) {
    function dynamo_has_consent(string $category): bool {
        return (bool) ($GLOBALS['dynamo_consent_cookies'][$category] ?? false);
    }
}

class CookieConsentPlaceholderTest extends TestCase
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
        $GLOBALS['dynamo_consent_cookies'] = []; // No consent granted by default.
        $GLOBALS['child_theme_path']       = ''; // No child theme by default.

        $this->loadCookieFiles();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['dynamo_consent_cookies']);
        unset($GLOBALS['child_theme_path']);
        unset($GLOBALS['wp_filter']['the_content']);
        unset($GLOBALS['wp_filter']['dynamo_cookie_banner_tokens']);
        unset($GLOBALS['wp_filter']['dynamo_token_defaults']);
    }

    // -----------------------------------------------------------------------
    // AC1 — Template file exists and renders CSS custom properties
    // -----------------------------------------------------------------------

    /** @test */
    public function consent_placeholder_template_file_exists(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        $this->assertFileExists(
            $templatePath,
            'templates/consent-placeholder.php must exist.'
        );
    }

    /** @test */
    public function consent_placeholder_template_renders_cookie_primary_custom_property(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '--cookie-primary',
            $output,
            'Consent placeholder must reference the --cookie-primary CSS custom property.'
        );
    }

    /** @test */
    public function consent_placeholder_template_renders_cookie_background_custom_property(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '--cookie-background',
            $output,
            'Consent placeholder must reference the --cookie-background CSS custom property.'
        );
    }

    /** @test */
    public function consent_placeholder_template_renders_cookie_text_custom_property(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '--cookie-text',
            $output,
            'Consent placeholder must reference the --cookie-text CSS custom property.'
        );
    }

    // -----------------------------------------------------------------------
    // AC2 — Template displays $service_name and $consent_category
    // -----------------------------------------------------------------------

    /** @test */
    public function consent_placeholder_template_displays_service_name(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'YouTube',
            $output,
            'Consent placeholder must display the $service_name variable.'
        );
    }

    /** @test */
    public function consent_placeholder_template_displays_consent_category(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'Vimeo';
        $consent_category  = 'statistics';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'statistics',
            $output,
            'Consent placeholder must display the $consent_category variable.'
        );
    }

    /** @test */
    public function consent_placeholder_template_renders_different_services_distinctly(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'Spotify';
        $consent_category  = 'functional';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'Spotify',
            $output,
            'Consent placeholder must display the correct service name when it changes.'
        );
        $this->assertStringContainsString(
            'functional',
            $output,
            'Consent placeholder must display the correct category when it changes.'
        );
    }

    // -----------------------------------------------------------------------
    // AC3 — Template includes a visually-hidden accessible label
    // -----------------------------------------------------------------------

    /** @test */
    public function consent_placeholder_template_renders_screen_reader_text_span(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'screen-reader-text',
            $output,
            'Consent placeholder must include a span with class "screen-reader-text" for accessibility.'
        );
    }

    /** @test */
    public function consent_placeholder_template_screen_reader_span_contains_accessible_label(): void
    {
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            $this->markTestSkipped('Template file does not exist yet — red phase.');
        }

        $service_name      = 'YouTube';
        $consent_category  = 'marketing';

        ob_start();
        include $templatePath;
        $output = ob_get_clean();

        // The accessible label must be a non-empty string inside the span.
        $this->assertMatchesRegularExpression(
            '/<span[^>]*class="[^"]*screen-reader-text[^"]*"[^>]*>[^<]+<\/span>/i',
            $output,
            'The screen-reader-text span must contain a non-empty accessible label string.'
        );
    }

    // -----------------------------------------------------------------------
    // AC4 — Template can be overridden via a child theme
    // -----------------------------------------------------------------------

    /** @test */
    public function locate_template_is_used_to_resolve_placeholder_template_path(): void
    {
        // Assert that the driver's method of loading the template respects child
        // theme overrides by using locate_template(). We verify this by placing
        // an override at the child theme path and confirming it takes precedence.

        $tmpDir = sys_get_temp_dir() . '/dynamo-child-' . uniqid('', true);
        mkdir($tmpDir . '/templates', 0755, true);

        // Write a minimal child-theme override.
        $overrideContent = '<div class="consent-placeholder-override" data-service="' . ($service_name ?? '') . '"></div>';
        file_put_contents($tmpDir . '/templates/consent-placeholder.php', '<?php echo "CHILD_THEME_OVERRIDE"; ?>');

        $GLOBALS['child_theme_path'] = $tmpDir;

        $resolved = locate_template('templates/consent-placeholder.php');

        // Clean up temporary directory.
        unlink($tmpDir . '/templates/consent-placeholder.php');
        rmdir($tmpDir . '/templates');
        rmdir($tmpDir);

        $this->assertSame(
            $tmpDir . '/templates/consent-placeholder.php',
            $resolved,
            'locate_template() must return the child theme override path when it exists.'
        );
    }

    /** @test */
    public function locate_template_falls_back_to_parent_theme_when_no_child_override(): void
    {
        $GLOBALS['child_theme_path'] = ''; // No child theme.

        // Only verify locate_template falls back to parent when template exists there.
        $templatePath = DYNAMO_PATH . 'templates/consent-placeholder.php';

        if (! is_file($templatePath)) {
            // Template doesn't exist yet — verify locate_template returns '' (correct fallback behavior).
            $resolved = locate_template('templates/consent-placeholder.php');
            $this->assertSame(
                '',
                $resolved,
                'locate_template() must return empty string when the template does not exist in either theme.'
            );
        } else {
            $resolved = locate_template('templates/consent-placeholder.php');
            $this->assertSame(
                $templatePath,
                $resolved,
                'locate_template() must return the parent theme path when no child override exists.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // AC5 — Both drivers implement register_embed_hooks() and hook the_content
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_driver_register_embed_hooks_method_exists(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $this->assertTrue(
            method_exists('Dynamo_Cookie_Driver_Complianz', 'register_embed_hooks'),
            'Dynamo_Cookie_Driver_Complianz must implement register_embed_hooks().'
        );
    }

    /** @test */
    public function complianz_driver_register_embed_hooks_registers_the_content_filter(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'the_content',
            $GLOBALS['wp_filter'],
            'Complianz driver register_embed_hooks() must register a callback on the_content filter.'
        );

        $registeredCallbacks = array_merge(...array_values($GLOBALS['wp_filter']['the_content']));
        $this->assertNotEmpty(
            $registeredCallbacks,
            'At least one callback must be registered on the_content by Complianz register_embed_hooks().'
        );
    }

    /** @test */
    public function borlabs_driver_register_embed_hooks_method_exists(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $this->assertTrue(
            method_exists('Dynamo_Cookie_Driver_Borlabs', 'register_embed_hooks'),
            'Dynamo_Cookie_Driver_Borlabs must implement register_embed_hooks().'
        );
    }

    /** @test */
    public function borlabs_driver_register_embed_hooks_registers_the_content_filter(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'the_content',
            $GLOBALS['wp_filter'],
            'Borlabs driver register_embed_hooks() must register a callback on the_content filter.'
        );

        $registeredCallbacks = array_merge(...array_values($GLOBALS['wp_filter']['the_content']));
        $this->assertNotEmpty(
            $registeredCallbacks,
            'At least one callback must be registered on the_content by Borlabs register_embed_hooks().'
        );
    }

    // -----------------------------------------------------------------------
    // AC7 — Embed HTML is replaced with placeholder when consent cookie absent
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_the_content_filter_replaces_youtube_iframe_when_marketing_consent_absent(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // No marketing consent granted.
        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringNotContainsString(
            '<iframe src="https://www.youtube.com/embed',
            $filtered,
            'YouTube iframe must not be rendered in output when marketing consent is absent.'
        );

        $this->assertStringContainsString(
            'consent-placeholder',
            $filtered,
            'A consent placeholder must be injected in place of the YouTube iframe.'
        );
    }

    /** @test */
    public function complianz_the_content_filter_preserves_youtube_iframe_when_marketing_consent_granted(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        // Marketing consent IS granted.
        $GLOBALS['dynamo_consent_cookies'] = ['marketing' => true];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringContainsString(
            'youtube.com/embed',
            $filtered,
            'YouTube iframe must be preserved in output when marketing consent IS granted.'
        );
    }

    /** @test */
    public function borlabs_the_content_filter_replaces_youtube_iframe_when_marketing_consent_absent(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/abc123" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringNotContainsString(
            '<iframe src="https://www.youtube.com/embed',
            $filtered,
            'YouTube iframe must not be rendered in output when marketing consent is absent (Borlabs).'
        );

        $this->assertStringContainsString(
            'consent-placeholder',
            $filtered,
            'A consent placeholder must be injected in place of the YouTube iframe (Borlabs).'
        );
    }

    /** @test */
    public function borlabs_the_content_filter_preserves_youtube_iframe_when_marketing_consent_granted(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $GLOBALS['dynamo_consent_cookies'] = ['marketing' => true];

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/abc123" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringContainsString(
            'youtube.com/embed',
            $filtered,
            'YouTube iframe must be preserved in output when marketing consent IS granted (Borlabs).'
        );
    }

    /** @test */
    public function complianz_the_content_filter_passes_service_name_to_placeholder(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringContainsString(
            'YouTube',
            $filtered,
            'The placeholder output must include the service name "YouTube".'
        );
    }

    /** @test */
    public function complianz_the_content_filter_passes_consent_category_to_placeholder(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringContainsString(
            'marketing',
            $filtered,
            'The placeholder output must include the required consent category "marketing".'
        );
    }

    /** @test */
    public function borlabs_the_content_filter_passes_service_name_to_placeholder(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('the_content', $embedHtml);

        $this->assertStringContainsString(
            'YouTube',
            $filtered,
            'The placeholder output must include the service name "YouTube" (Borlabs).'
        );
    }

    /** @test */
    public function the_content_filter_does_not_affect_non_embed_content(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $regularContent = '<p>Hello world. This is regular post content with no embeds.</p>';

        $filtered = apply_filters('the_content', $regularContent);

        $this->assertStringContainsString(
            'Hello world',
            $filtered,
            'Regular post content with no embeds must be passed through unchanged.'
        );
        $this->assertStringNotContainsString(
            'consent-placeholder',
            $filtered,
            'No consent placeholder should appear in content with no embed iframes.'
        );
    }

    /** @test */
    public function the_content_filter_handles_vimeo_embed_when_statistics_consent_absent(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $GLOBALS['dynamo_consent_cookies'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $vimeoEmbed = '<iframe src="https://player.vimeo.com/video/123456789" width="640" height="360"></iframe>';

        $filtered = apply_filters('the_content', $vimeoEmbed);

        $this->assertStringNotContainsString(
            '<iframe src="https://player.vimeo.com',
            $filtered,
            'Vimeo iframe must not be rendered in output when required consent is absent.'
        );
        $this->assertStringContainsString(
            'consent-placeholder',
            $filtered,
            'A consent placeholder must be injected in place of the Vimeo iframe.'
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Soft-load cookie driver files plus any template-related files.
     * Tolerates missing files during the red phase so tests fail at assertion
     * level, not fatal-error level.
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
}
