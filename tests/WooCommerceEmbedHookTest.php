<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * TDD Red Phase — Issue #31: WooCommerce Embed Hook Extension
 *
 * These tests are written BEFORE the implementation exists and are expected
 * to fail until the production code satisfies all acceptance criteria.
 *
 * Acceptance criteria covered (unit-test scope, AC6):
 *
 *  AC1 — When WooCommerce is active, both drivers hook into
 *         woocommerce_short_description in addition to the_content.
 *
 *  AC2 — Both drivers hook into term_description, guarded by
 *         is_product_category() || is_product_tag(), so non-WooCommerce term
 *         descriptions are unaffected.
 *
 *  AC5 — Neither hook is registered if WooCommerce is not active.
 *
 *  AC6 — Unit tests assert that the short_description and term_description
 *         filters are registered when WooCommerce is present and absent.
 *
 * WooCommerce detection strategy:
 *   Production code calls class_exists('WooCommerce') (standard WP pattern).
 *   Tests that require WC "active" use @runInSeparateProcess so that they run
 *   in a freshly bootstrapped PHP process. The WooCommerce class stub is
 *   injected via eval() at the start of each such test — eval() is the only
 *   mechanism available inside a method body that creates a named class
 *   without triggering a "class declarations may not be nested" fatal.
 *
 *   WC-absent tests run normally: bootstrap.php does not define WooCommerce,
 *   so class_exists('WooCommerce') returns false.
 */

// ---------------------------------------------------------------------------
// Soft-load cookie driver files — tolerates missing files during red phase
// so tests fail at the assertion level rather than with a fatal error.
// ---------------------------------------------------------------------------
if (! function_exists('_dynamo_load_cookie_files')) {
    function _dynamo_load_cookie_files(): void {
        $files = [
            DYNAMO_PATH . 'includes/cookie/interface-dynamo-cookie-driver.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-consent-placeholder.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-complianz.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-borlabs.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// locate_template() stub — needed by Dynamo_Consent_Placeholder if not
// already defined (e.g. when this file runs before CookieConsentPlaceholderTest).
// ---------------------------------------------------------------------------
if (! function_exists('locate_template')) {
    function locate_template(string|array $template_names, bool $load = false, bool $require_once = true): string {
        $names = (array) $template_names;
        foreach ($names as $name) {
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
// dynamo_has_consent() stub — reads from global cookie jar so placeholder
// replacement behaves consistently across all tests.
// ---------------------------------------------------------------------------
if (! function_exists('dynamo_has_consent')) {
    function dynamo_has_consent(string $category): bool {
        return (bool) ($GLOBALS['dynamo_consent_cookies'][$category] ?? false);
    }
}

class WooCommerceEmbedHookTest extends TestCase
{
    // -----------------------------------------------------------------------
    // setUp / tearDown
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        $GLOBALS['wp_is_product_category'] = false;
        $GLOBALS['wp_is_product_tag']      = false;
        $GLOBALS['child_theme_path']       = '';

        _dynamo_load_cookie_files();
    }

    protected function tearDown(): void
    {
        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        unset($GLOBALS['wp_is_product_category']);
        unset($GLOBALS['wp_is_product_tag']);
    }

    // =======================================================================
    // AC5 + AC6 — WC ABSENT: hooks must NOT be registered
    //   The bootstrap does not define class WooCommerce, so
    //   class_exists('WooCommerce') === false in the normal process.
    // =======================================================================

    /** @test */
    public function complianz_does_not_register_short_description_filter_when_woocommerce_absent(): void
    {
        $this->assertFalse(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must not exist in this process.'
        );

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $this->assertArrayNotHasKey(
            'woocommerce_short_description',
            $GLOBALS['wp_filter'],
            'Complianz must NOT register woocommerce_short_description when WooCommerce is absent.'
        );
    }

    /** @test */
    public function borlabs_does_not_register_short_description_filter_when_woocommerce_absent(): void
    {
        $this->assertFalse(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must not exist in this process.'
        );

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $this->assertArrayNotHasKey(
            'woocommerce_short_description',
            $GLOBALS['wp_filter'],
            'Borlabs must NOT register woocommerce_short_description when WooCommerce is absent.'
        );
    }

    /** @test */
    public function complianz_does_not_register_term_description_filter_when_woocommerce_absent(): void
    {
        $this->assertFalse(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must not exist in this process.'
        );

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $this->assertArrayNotHasKey(
            'term_description',
            $GLOBALS['wp_filter'],
            'Complianz must NOT register term_description when WooCommerce is absent.'
        );
    }

    /** @test */
    public function borlabs_does_not_register_term_description_filter_when_woocommerce_absent(): void
    {
        $this->assertFalse(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must not exist in this process.'
        );

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $this->assertArrayNotHasKey(
            'term_description',
            $GLOBALS['wp_filter'],
            'Borlabs must NOT register term_description when WooCommerce is absent.'
        );
    }

    // -----------------------------------------------------------------------
    // AC2 guard logic — directly exercises is_product_category / is_product_tag
    // stubs without needing WooCommerce present.  A test callback mirrors the
    // expected driver implementation so the guard itself can be verified.
    // -----------------------------------------------------------------------

    /** @test */
    public function term_description_filter_passes_embed_through_when_not_on_product_taxonomy(): void
    {
        $GLOBALS['wp_is_product_category'] = false;
        $GLOBALS['wp_is_product_tag']      = false;

        // Mirror the guard the implementation is required to apply.
        add_filter('term_description', static function (string $content): string {
            if (! is_product_category() && ! is_product_tag()) {
                return $content;
            }
            return Dynamo_Consent_Placeholder::replace_embeds($content);
        });

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('term_description', $embedHtml);

        $this->assertSame(
            $embedHtml,
            $filtered,
            'term_description filter must pass embed through unchanged when not on a product taxonomy page.'
        );
    }

    // =======================================================================
    // AC1 + AC6 — WC ACTIVE: woocommerce_short_description IS registered
    //   Each test runs in a separate process and injects class WooCommerce via
    //   eval() so the bootstrap remains clean.
    // =======================================================================

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function complianz_registers_short_description_filter_when_woocommerce_active(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        $this->assertTrue(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must exist in this process.'
        );

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_enqueued_scripts'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'woocommerce_short_description',
            $GLOBALS['wp_filter'],
            'Complianz must register woocommerce_short_description filter when WooCommerce is active.'
        );

        $callbacks = array_merge(...array_values($GLOBALS['wp_filter']['woocommerce_short_description']));
        $this->assertNotEmpty(
            $callbacks,
            'At least one callback must be registered on woocommerce_short_description by Complianz.'
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function borlabs_registers_short_description_filter_when_woocommerce_active(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        $this->assertTrue(
            class_exists('WooCommerce'),
            'Precondition: WooCommerce class must exist in this process.'
        );

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_enqueued_scripts'] = [];

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'woocommerce_short_description',
            $GLOBALS['wp_filter'],
            'Borlabs must register woocommerce_short_description filter when WooCommerce is active.'
        );

        $callbacks = array_merge(...array_values($GLOBALS['wp_filter']['woocommerce_short_description']));
        $this->assertNotEmpty(
            $callbacks,
            'At least one callback must be registered on woocommerce_short_description by Borlabs.'
        );
    }

    // =======================================================================
    // AC2 + AC6 — WC ACTIVE: term_description IS registered
    // =======================================================================

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function complianz_registers_term_description_filter_when_woocommerce_active(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_enqueued_scripts'] = [];

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'term_description',
            $GLOBALS['wp_filter'],
            'Complianz must register term_description filter when WooCommerce is active.'
        );

        $callbacks = array_merge(...array_values($GLOBALS['wp_filter']['term_description']));
        $this->assertNotEmpty(
            $callbacks,
            'At least one callback must be registered on term_description by Complianz.'
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function borlabs_registers_term_description_filter_when_woocommerce_active(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_enqueued_scripts'] = [];

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $this->assertArrayHasKey(
            'term_description',
            $GLOBALS['wp_filter'],
            'Borlabs must register term_description filter when WooCommerce is active.'
        );

        $callbacks = array_merge(...array_values($GLOBALS['wp_filter']['term_description']));
        $this->assertNotEmpty(
            $callbacks,
            'At least one callback must be registered on term_description by Borlabs.'
        );
    }

    // =======================================================================
    // AC2 — WC ACTIVE: term_description replaces embeds on product taxonomy
    // =======================================================================

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function complianz_term_description_replaces_youtube_embed_on_product_category_page(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        $GLOBALS['wp_is_product_category'] = true;
        $GLOBALS['wp_is_product_tag']      = false;

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('term_description', $embedHtml);

        $this->assertStringNotContainsString(
            '<iframe src="https://www.youtube.com/embed',
            $filtered,
            'Complianz term_description filter must replace YouTube iframe on a product category page.'
        );
        $this->assertStringContainsString(
            'consent-placeholder',
            $filtered,
            'Complianz term_description filter must inject a consent placeholder on a product category page.'
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function borlabs_term_description_replaces_youtube_embed_on_product_category_page(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        $GLOBALS['wp_is_product_category'] = true;
        $GLOBALS['wp_is_product_tag']      = false;

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('term_description', $embedHtml);

        $this->assertStringNotContainsString(
            '<iframe src="https://www.youtube.com/embed',
            $filtered,
            'Borlabs term_description filter must replace YouTube iframe on a product category page.'
        );
        $this->assertStringContainsString(
            'consent-placeholder',
            $filtered,
            'Borlabs term_description filter must inject a consent placeholder on a product category page.'
        );
    }

    // =======================================================================
    // AC2 — WC ACTIVE: term_description passes through on non-product taxonomy
    // =======================================================================

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function complianz_term_description_passes_through_when_not_on_product_taxonomy(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        $GLOBALS['wp_is_product_category'] = false;
        $GLOBALS['wp_is_product_tag']      = false;

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('term_description', $embedHtml);

        $this->assertSame(
            $embedHtml,
            $filtered,
            'Complianz term_description must pass embed through unchanged on a non-product taxonomy page.'
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function borlabs_term_description_passes_through_when_not_on_product_taxonomy(): void
    {
        eval('if (!class_exists("WooCommerce")) { class WooCommerce {} }');

        _dynamo_load_cookie_files();

        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_enqueued_scripts']    = [];
        $GLOBALS['dynamo_consent_cookies'] = [];
        $GLOBALS['wp_is_product_category'] = false;
        $GLOBALS['wp_is_product_tag']      = false;

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $driver->register_embed_hooks();

        $embedHtml = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>';

        $filtered = apply_filters('term_description', $embedHtml);

        $this->assertSame(
            $embedHtml,
            $filtered,
            'Borlabs term_description must pass embed through unchanged on a non-product taxonomy page.'
        );
    }
}
