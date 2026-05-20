<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 1 (TDD red phase) tests for Issue #26 — Cookie Compat Class: Foundation.
 *
 * These tests are written BEFORE the implementation exists. They are expected
 * to fail until the corresponding production classes are added under
 * `includes/cookie/` and wired up in `functions.php`.
 *
 * Acceptance criteria covered:
 *  1. Dynamo_Cookie_Driver interface defines the required methods.
 *  2. Dynamo_Cookie_Integration hooks on after_setup_theme priority 11
 *     and instantiates Complianz driver when Complianz is active.
 *  3. Dynamo_Cookie_Integration instantiates Borlabs driver when Borlabs
 *     Cookie is active.
 *  4. When both plugins are active, _doing_it_wrong() is called and the
 *     Complianz driver wins.
 *  5. When neither plugin is active, no driver is instantiated and no
 *     hooks are registered.
 *  6. functions.php requires the new cookie class files and registers
 *     the integration on after_setup_theme priority 11.
 *  7. All new cookie class files live under includes/cookie/.
 *  8. Detection scenarios are exercised: Complianz only, Borlabs only,
 *     both active, neither active.
 *
 * Design contract for the implementation under test (defined here so the
 * implementer has an explicit spec to satisfy):
 *
 *   interface Dynamo_Cookie_Driver {
 *       public function register_palette_sync_hooks(): void;
 *       public function register_embed_hooks(): void;
 *       public function get_consent_categories(): array;
 *   }
 *
 *   class Dynamo_Cookie_Driver_Complianz implements Dynamo_Cookie_Driver { ... }
 *   class Dynamo_Cookie_Driver_Borlabs   implements Dynamo_Cookie_Driver { ... }
 *
 *   class Dynamo_Cookie_Integration {
 *       public function __construct(
 *           ?callable $complianz_detector = null,
 *           ?callable $borlabs_detector   = null
 *       );
 *       public static function boot(): void; // registers after_setup_theme @11
 *       public function detect_and_register(): void;
 *       public function get_active_driver(): ?Dynamo_Cookie_Driver;
 *   }
 */
class CookieIntegrationTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']            = [];
        $GLOBALS['wp_theme_supports']    = [];
        $GLOBALS['wp_removed_actions']   = [];
        $GLOBALS['wp_enqueued_styles']   = [];
        $GLOBALS['wp_doing_it_wrong']    = [];
    }

    // ---------------------------------------------------------------
    // AC 7 — Files live under includes/cookie/
    // ---------------------------------------------------------------

    public function test_cookie_driver_interface_file_exists_under_includes_cookie(): void {
        $this->assertFileExists(
            DYNAMO_PATH . 'includes/cookie/interface-dynamo-cookie-driver.php',
            'Dynamo_Cookie_Driver interface file must live under includes/cookie/.'
        );
    }

    public function test_cookie_integration_file_exists_under_includes_cookie(): void {
        $this->assertFileExists(
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-integration.php',
            'Dynamo_Cookie_Integration class file must live under includes/cookie/.'
        );
    }

    public function test_complianz_driver_file_exists_under_includes_cookie(): void {
        $this->assertFileExists(
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-complianz.php',
            'Dynamo_Cookie_Driver_Complianz class file must live under includes/cookie/.'
        );
    }

    public function test_borlabs_driver_file_exists_under_includes_cookie(): void {
        $this->assertFileExists(
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-borlabs.php',
            'Dynamo_Cookie_Driver_Borlabs class file must live under includes/cookie/.'
        );
    }

    // ---------------------------------------------------------------
    // AC 1 — Interface shape
    // ---------------------------------------------------------------

    public function test_dynamo_cookie_driver_interface_is_defined(): void {
        $this->loadCookieFiles();
        $this->assertTrue(
            interface_exists('Dynamo_Cookie_Driver'),
            'Dynamo_Cookie_Driver interface must be defined.'
        );
    }

    public function test_dynamo_cookie_driver_interface_declares_register_palette_sync_hooks(): void {
        $this->loadCookieFiles();
        $reflection = new ReflectionClass('Dynamo_Cookie_Driver');
        $this->assertTrue($reflection->hasMethod('register_palette_sync_hooks'));
        $method = $reflection->getMethod('register_palette_sync_hooks');
        $this->assertTrue(
            $method->hasReturnType() && (string) $method->getReturnType() === 'void',
            'register_palette_sync_hooks() must declare a void return type.'
        );
        $this->assertSame(0, $method->getNumberOfParameters());
    }

    public function test_dynamo_cookie_driver_interface_declares_register_embed_hooks(): void {
        $this->loadCookieFiles();
        $reflection = new ReflectionClass('Dynamo_Cookie_Driver');
        $this->assertTrue($reflection->hasMethod('register_embed_hooks'));
        $method = $reflection->getMethod('register_embed_hooks');
        $this->assertTrue(
            $method->hasReturnType() && (string) $method->getReturnType() === 'void',
            'register_embed_hooks() must declare a void return type.'
        );
        $this->assertSame(0, $method->getNumberOfParameters());
    }

    public function test_dynamo_cookie_driver_interface_declares_get_consent_categories(): void {
        $this->loadCookieFiles();
        $reflection = new ReflectionClass('Dynamo_Cookie_Driver');
        $this->assertTrue($reflection->hasMethod('get_consent_categories'));
        $method = $reflection->getMethod('get_consent_categories');
        $this->assertTrue(
            $method->hasReturnType() && (string) $method->getReturnType() === 'array',
            'get_consent_categories() must declare an array return type.'
        );
        $this->assertSame(0, $method->getNumberOfParameters());
    }

    public function test_complianz_driver_implements_driver_interface(): void {
        $this->loadCookieFiles();
        $this->assertTrue(class_exists('Dynamo_Cookie_Driver_Complianz'));
        $this->assertContains(
            'Dynamo_Cookie_Driver',
            class_implements('Dynamo_Cookie_Driver_Complianz') ?: [],
            'Dynamo_Cookie_Driver_Complianz must implement Dynamo_Cookie_Driver.'
        );
    }

    public function test_borlabs_driver_implements_driver_interface(): void {
        $this->loadCookieFiles();
        $this->assertTrue(class_exists('Dynamo_Cookie_Driver_Borlabs'));
        $this->assertContains(
            'Dynamo_Cookie_Driver',
            class_implements('Dynamo_Cookie_Driver_Borlabs') ?: [],
            'Dynamo_Cookie_Driver_Borlabs must implement Dynamo_Cookie_Driver.'
        );
    }

    // ---------------------------------------------------------------
    // AC 2 — after_setup_theme priority 11 hook registration
    // ---------------------------------------------------------------

    public function test_boot_registers_on_after_setup_theme_at_priority_11(): void {
        $this->loadCookieFiles();
        Dynamo_Cookie_Integration::boot();

        $this->assertArrayHasKey(
            'after_setup_theme',
            $GLOBALS['wp_filter'],
            'Dynamo_Cookie_Integration::boot() must register on after_setup_theme.'
        );
        $this->assertArrayHasKey(
            11,
            $GLOBALS['wp_filter']['after_setup_theme'],
            'Dynamo_Cookie_Integration::boot() must register at priority 11.'
        );
        $this->assertNotEmpty(
            $GLOBALS['wp_filter']['after_setup_theme'][11],
            'A callback must be registered at after_setup_theme priority 11.'
        );
    }

    // ---------------------------------------------------------------
    // AC 2 + AC 8 — Complianz only
    // ---------------------------------------------------------------

    public function test_complianz_only_instantiates_complianz_driver(): void {
        $this->loadCookieFiles();
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,   // Complianz active
            static fn(): bool => false   // Borlabs inactive
        );
        $integration->detect_and_register();

        $driver = $integration->get_active_driver();
        $this->assertNotNull($driver, 'A driver must be instantiated when Complianz is active.');
        $this->assertInstanceOf('Dynamo_Cookie_Driver_Complianz', $driver);
        $this->assertEmpty(
            $GLOBALS['wp_doing_it_wrong'] ?? [],
            '_doing_it_wrong() must NOT be triggered for the Complianz-only scenario.'
        );
    }

    // ---------------------------------------------------------------
    // AC 3 + AC 8 — Borlabs only
    // ---------------------------------------------------------------

    public function test_borlabs_only_instantiates_borlabs_driver(): void {
        $this->loadCookieFiles();
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => false,  // Complianz inactive
            static fn(): bool => true    // Borlabs active
        );
        $integration->detect_and_register();

        $driver = $integration->get_active_driver();
        $this->assertNotNull($driver, 'A driver must be instantiated when Borlabs is active.');
        $this->assertInstanceOf('Dynamo_Cookie_Driver_Borlabs', $driver);
        $this->assertEmpty(
            $GLOBALS['wp_doing_it_wrong'] ?? [],
            '_doing_it_wrong() must NOT be triggered for the Borlabs-only scenario.'
        );
    }

    // ---------------------------------------------------------------
    // AC 4 + AC 8 — Both active: _doing_it_wrong + Complianz wins
    // ---------------------------------------------------------------

    public function test_both_plugins_active_triggers_doing_it_wrong_and_uses_complianz(): void {
        $this->loadCookieFiles();
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,   // Complianz active
            static fn(): bool => true    // Borlabs active
        );
        $integration->detect_and_register();

        $this->assertNotEmpty(
            $GLOBALS['wp_doing_it_wrong'] ?? [],
            '_doing_it_wrong() must be triggered when both plugins are active.'
        );

        $driver = $integration->get_active_driver();
        $this->assertInstanceOf(
            'Dynamo_Cookie_Driver_Complianz',
            $driver,
            'Complianz must win when both plugins are active.'
        );
    }

    // ---------------------------------------------------------------
    // AC 5 + AC 8 — Neither active: no driver, no hooks
    // ---------------------------------------------------------------

    public function test_neither_plugin_active_does_not_instantiate_driver(): void {
        $this->loadCookieFiles();

        // Reset filters so any prior boot()/detect() does not pollute the
        // assertion about hook registration.
        $GLOBALS['wp_filter'] = [];

        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => false,  // Complianz inactive
            static fn(): bool => false   // Borlabs inactive
        );
        $integration->detect_and_register();

        $this->assertNull(
            $integration->get_active_driver(),
            'No driver must be instantiated when neither plugin is active.'
        );
        $this->assertEmpty(
            $GLOBALS['wp_filter'],
            'No hooks must be registered when neither plugin is active.'
        );
        $this->assertEmpty(
            $GLOBALS['wp_doing_it_wrong'] ?? [],
            '_doing_it_wrong() must NOT be triggered for the neither-active scenario.'
        );
    }

    // ---------------------------------------------------------------
    // Active driver's hook registration methods are invoked
    // ---------------------------------------------------------------

    public function test_detect_and_register_invokes_driver_hook_registration(): void {
        $this->loadCookieFiles();

        // Reset filters before so we can observe what the driver registered.
        $GLOBALS['wp_filter'] = [];

        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false
        );
        $integration->detect_and_register();

        $this->assertNotEmpty(
            $GLOBALS['wp_filter'],
            'The active driver must register at least one hook via '
            . 'register_palette_sync_hooks() / register_embed_hooks().'
        );
    }

    // ---------------------------------------------------------------
    // AC 6 — functions.php wiring
    // ---------------------------------------------------------------

    public function test_functions_php_requires_cookie_interface_file(): void {
        $contents = (string) file_get_contents(DYNAMO_PATH . 'functions.php');
        $this->assertMatchesRegularExpression(
            '#require(_once)?\s+[^;]*includes/cookie/interface-dynamo-cookie-driver\.php#',
            $contents,
            'functions.php must require the cookie driver interface file.'
        );
    }

    public function test_functions_php_requires_cookie_integration_class_file(): void {
        $contents = (string) file_get_contents(DYNAMO_PATH . 'functions.php');
        $this->assertMatchesRegularExpression(
            '#require(_once)?\s+[^;]*includes/cookie/class-dynamo-cookie-integration\.php#',
            $contents,
            'functions.php must require the Dynamo_Cookie_Integration class file.'
        );
    }

    public function test_functions_php_requires_complianz_driver_file(): void {
        $contents = (string) file_get_contents(DYNAMO_PATH . 'functions.php');
        $this->assertMatchesRegularExpression(
            '#require(_once)?\s+[^;]*includes/cookie/class-dynamo-cookie-driver-complianz\.php#',
            $contents,
            'functions.php must require the Complianz driver file.'
        );
    }

    public function test_functions_php_requires_borlabs_driver_file(): void {
        $contents = (string) file_get_contents(DYNAMO_PATH . 'functions.php');
        $this->assertMatchesRegularExpression(
            '#require(_once)?\s+[^;]*includes/cookie/class-dynamo-cookie-driver-borlabs\.php#',
            $contents,
            'functions.php must require the Borlabs driver file.'
        );
    }

    public function test_functions_php_boots_cookie_integration_at_after_setup_theme_priority_11(): void {
        $contents = (string) file_get_contents(DYNAMO_PATH . 'functions.php');

        // Look for an after_setup_theme add_action with priority 11 that
        // references the cookie integration. Accept either a static
        // ::boot() invocation or a closure that instantiates the class.
        $this->assertMatchesRegularExpression(
            '#add_action\(\s*[\'"]after_setup_theme[\'"][^;]*Dynamo_Cookie_Integration[^;]*\b11\b#s',
            $contents,
            'functions.php must register Dynamo_Cookie_Integration on '
            . 'after_setup_theme at priority 11.'
        );
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Best-effort loader for the cookie files. They do not exist during the
     * red phase of TDD, so we tolerate missing files. Each test that requires
     * a specific symbol then asserts its presence explicitly, which produces
     * a clear failure message instead of a fatal "require: no such file".
     */
    private function loadCookieFiles(): void {
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
}
