<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * TDD Red Phase — Issue #28: Cookie Categories REST Endpoint
 *
 * These tests are written BEFORE the implementation exists and are expected
 * to fail until the production code satisfies all acceptance criteria.
 *
 * Acceptance criteria covered:
 *
 *  AC1 — Both drivers implement get_consent_categories() returning a keyed
 *         label map: [['slug' => '...', 'label' => '...'], ...]
 *
 *  AC2 — The Complianz driver returns all four fixed categories
 *         (marketing, statistics, functional, preferences) with
 *         human-readable labels.
 *
 *  AC3 — The Borlabs driver returns an array where each item has 'slug' and
 *         'label' keys. Falls back gracefully (empty array) when Borlabs
 *         classes are not present.
 *
 *  AC4 — A REST route is registered at /wp-json/dynamo/v1/cookie-categories
 *         requiring the edit_posts capability.
 *
 *  AC5 — The endpoint returns HTTP 200 with a JSON array of {slug, label}
 *         objects when the active plugin is detected.
 *
 *  AC6 — Unit tests assert a 401-equivalent response for unauthenticated
 *         requests (permission callback returns false when the current user
 *         cannot edit_posts).
 */

// ---------------------------------------------------------------------------
// REST stub — not present in bootstrap.php, defined here so these tests can
// capture register_rest_route() calls without polluting the global bootstrap.
// ---------------------------------------------------------------------------
if (! function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args = []): void {
        $GLOBALS['wp_rest_routes'][] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
    }
}

class CookieCategoriesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Test lifecycle
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']           = [];
        $GLOBALS['wp_rest_routes']      = [];
        $GLOBALS['wp_doing_it_wrong']   = [];
        $GLOBALS['wp_current_user_can'] = [];

        $this->loadCookieFiles();
    }

    // -----------------------------------------------------------------------
    // AC1 / AC2 — Complianz driver: return shape and required categories
    // -----------------------------------------------------------------------

    /** @test */
    public function complianz_get_consent_categories_returns_an_array(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $this->assertIsArray($result, 'get_consent_categories() must return an array.');
    }

    /** @test */
    public function complianz_get_consent_categories_returns_exactly_four_items(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $this->assertCount(
            4,
            $result,
            'Complianz driver must return exactly 4 consent categories (marketing, statistics, functional, preferences).'
        );
    }

    /** @test */
    public function complianz_get_consent_categories_each_item_has_slug_key(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        foreach ($result as $index => $item) {
            $this->assertIsArray($item, "Item at index {$index} must be an array.");
            $this->assertArrayHasKey(
                'slug',
                $item,
                "Item at index {$index} must have a 'slug' key."
            );
        }
    }

    /** @test */
    public function complianz_get_consent_categories_each_item_has_label_key(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        foreach ($result as $index => $item) {
            $this->assertIsArray($item, "Item at index {$index} must be an array.");
            $this->assertArrayHasKey(
                'label',
                $item,
                "Item at index {$index} must have a 'label' key."
            );
        }
    }

    /** @test */
    public function complianz_get_consent_categories_includes_marketing_slug(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $slugs = array_column($result, 'slug');
        $this->assertContains(
            'marketing',
            $slugs,
            "Complianz driver must include a category with slug 'marketing'."
        );
    }

    /** @test */
    public function complianz_get_consent_categories_includes_statistics_slug(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $slugs = array_column($result, 'slug');
        $this->assertContains(
            'statistics',
            $slugs,
            "Complianz driver must include a category with slug 'statistics'."
        );
    }

    /** @test */
    public function complianz_get_consent_categories_includes_functional_slug(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $slugs = array_column($result, 'slug');
        $this->assertContains(
            'functional',
            $slugs,
            "Complianz driver must include a category with slug 'functional'."
        );
    }

    /** @test */
    public function complianz_get_consent_categories_includes_preferences_slug(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        $slugs = array_column($result, 'slug');
        $this->assertContains(
            'preferences',
            $slugs,
            "Complianz driver must include a category with slug 'preferences'."
        );
    }

    /** @test */
    public function complianz_get_consent_categories_labels_are_non_empty_strings(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Complianz');

        $driver = new Dynamo_Cookie_Driver_Complianz();
        $result = $driver->get_consent_categories();

        foreach ($result as $index => $item) {
            $this->assertIsString(
                $item['label'],
                "Item at index {$index} must have a string 'label'."
            );
            $this->assertNotEmpty(
                $item['label'],
                "Item at index {$index} must have a non-empty 'label'."
            );
        }
    }

    // -----------------------------------------------------------------------
    // AC1 / AC3 — Borlabs driver: return shape (empty or slug/label maps)
    // -----------------------------------------------------------------------

    /** @test */
    public function borlabs_get_consent_categories_returns_an_array(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $result = $driver->get_consent_categories();

        $this->assertIsArray($result, 'Borlabs get_consent_categories() must return an array.');
    }

    /** @test */
    public function borlabs_get_consent_categories_each_item_has_slug_and_label_keys(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $result = $driver->get_consent_categories();

        // When Borlabs is not present, an empty array is acceptable.
        // When items are returned, each must conform to the slug/label shape.
        foreach ($result as $index => $item) {
            $this->assertIsArray(
                $item,
                "Borlabs category at index {$index} must be an array, not a plain string."
            );
            $this->assertArrayHasKey(
                'slug',
                $item,
                "Borlabs category at index {$index} must have a 'slug' key."
            );
            $this->assertArrayHasKey(
                'label',
                $item,
                "Borlabs category at index {$index} must have a 'label' key."
            );
        }
    }

    /** @test */
    public function borlabs_get_consent_categories_does_not_return_plain_strings(): void
    {
        $this->assertClassExists('Dynamo_Cookie_Driver_Borlabs');

        $driver = new Dynamo_Cookie_Driver_Borlabs();
        $result = $driver->get_consent_categories();

        foreach ($result as $index => $item) {
            $this->assertIsArray(
                $item,
                "Borlabs category at index {$index} must be an associative array with 'slug'/'label' keys, not a plain string."
            );
        }
    }

    // -----------------------------------------------------------------------
    // AC4 — REST route is registered at dynamo/v1 / cookie-categories
    // -----------------------------------------------------------------------

    /** @test */
    public function rest_route_is_registered_with_correct_namespace(): void
    {
        // Trigger route registration with Complianz active.
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $this->assertNotEmpty(
            $GLOBALS['wp_rest_routes'] ?? [],
            'A REST route must be registered when an active cookie driver is detected.'
        );

        $namespaces = array_column($GLOBALS['wp_rest_routes'], 'namespace');
        $this->assertContains(
            'dynamo/v1',
            $namespaces,
            "REST route must be registered under the 'dynamo/v1' namespace."
        );
    }

    /** @test */
    public function rest_route_is_registered_at_cookie_categories_path(): void
    {
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $routes = array_column($GLOBALS['wp_rest_routes'], 'route');
        $this->assertContains(
            '/cookie-categories',
            $routes,
            "REST route must be registered at '/cookie-categories'."
        );
    }

    /** @test */
    public function rest_route_is_registered_for_get_method(): void
    {
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $registered = $this->findCookieCategoriesRoute();
        $this->assertNotNull($registered, 'cookie-categories REST route must be registered.');

        $args = $registered['args'];

        // The route args may be a flat array (single method) or a nested array
        // (multiple methods). Accept both WP_REST_Server::READABLE and 'GET'.
        $methods = $this->extractMethods($args);
        $this->assertNotEmpty(
            $methods,
            'The cookie-categories REST route must declare a GET/READABLE method.'
        );
    }

    /** @test */
    public function rest_route_is_not_registered_when_no_driver_active(): void
    {
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => false,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $this->assertEmpty(
            $GLOBALS['wp_rest_routes'] ?? [],
            'No REST route must be registered when no cookie plugin is active.'
        );
    }

    // -----------------------------------------------------------------------
    // AC4 — Permission callback requires edit_posts
    // -----------------------------------------------------------------------

    /** @test */
    public function rest_route_permission_callback_returns_false_when_user_cannot_edit_posts(): void
    {
        // Simulate an unauthenticated (or unprivileged) user.
        $GLOBALS['wp_current_user_can']['edit_posts'] = false;

        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $registered = $this->findCookieCategoriesRoute();
        $this->assertNotNull($registered, 'cookie-categories REST route must be registered.');

        $permissionCallback = $this->extractPermissionCallback($registered['args']);
        $this->assertNotNull(
            $permissionCallback,
            'The cookie-categories REST route must declare a permission_callback.'
        );

        $result = $permissionCallback();
        $this->assertFalse(
            $result,
            'Permission callback must return false when the current user cannot edit_posts (unauthenticated/401 scenario).'
        );
    }

    /** @test */
    public function rest_route_permission_callback_returns_true_when_user_can_edit_posts(): void
    {
        // Simulate an authenticated editor.
        $GLOBALS['wp_current_user_can']['edit_posts'] = true;

        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $registered = $this->findCookieCategoriesRoute();
        $this->assertNotNull($registered, 'cookie-categories REST route must be registered.');

        $permissionCallback = $this->extractPermissionCallback($registered['args']);
        $this->assertNotNull(
            $permissionCallback,
            'The cookie-categories REST route must declare a permission_callback.'
        );

        $result = $permissionCallback();
        $this->assertTrue(
            $result,
            'Permission callback must return true when the current user can edit_posts.'
        );
    }

    // -----------------------------------------------------------------------
    // AC5 — Callback returns correct shape from active driver
    // -----------------------------------------------------------------------

    /** @test */
    public function rest_route_callback_returns_array_of_slug_label_maps(): void
    {
        $integration = new Dynamo_Cookie_Integration(
            static fn(): bool => true,  // Complianz active
            static fn(): bool => false,
        );
        $integration->detect_and_register();

        $registered = $this->findCookieCategoriesRoute();
        $this->assertNotNull($registered, 'cookie-categories REST route must be registered.');

        $callback = $this->extractCallback($registered['args']);
        $this->assertNotNull($callback, 'The cookie-categories REST route must declare a callback.');

        $response = $callback();

        // The callback may return an array directly or a WP_REST_Response-like object.
        // For unit-test purposes we accept either; extract the data.
        $data = is_array($response) ? $response : (method_exists($response, 'get_data') ? $response->get_data() : null);

        $this->assertIsArray($data, 'REST callback must return an array of categories.');

        // Complianz should give us 4 items.
        $this->assertCount(4, $data, 'REST callback must return 4 categories for the Complianz driver.');

        foreach ($data as $index => $item) {
            $this->assertIsArray($item, "Category at index {$index} must be an associative array.");
            $this->assertArrayHasKey('slug', $item, "Category at index {$index} must have 'slug'.");
            $this->assertArrayHasKey('label', $item, "Category at index {$index} must have 'label'.");
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Soft-load the cookie driver files. Files exist but contain stubs;
     * tests will fail at the assertion level rather than with a fatal require.
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

    private function assertClassExists(string $className): void
    {
        $this->assertTrue(
            class_exists($className),
            "Class {$className} must exist. Ensure the cookie driver files are loaded."
        );
    }

    /**
     * Find the registered route entry for dynamo/v1 + /cookie-categories.
     */
    private function findCookieCategoriesRoute(): ?array
    {
        foreach ($GLOBALS['wp_rest_routes'] ?? [] as $route) {
            if ($route['namespace'] === 'dynamo/v1' && $route['route'] === '/cookie-categories') {
                return $route;
            }
        }
        return null;
    }

    /**
     * Extract methods declared in the REST route args.
     * Handles both flat ('methods' key at top level) and nested arrays.
     */
    private function extractMethods(array $args): array
    {
        if (isset($args['methods'])) {
            return is_array($args['methods']) ? $args['methods'] : [$args['methods']];
        }
        // Nested format: $args is an array of method-group arrays.
        $methods = [];
        foreach ($args as $group) {
            if (is_array($group) && isset($group['methods'])) {
                $methods = array_merge(
                    $methods,
                    is_array($group['methods']) ? $group['methods'] : [$group['methods']]
                );
            }
        }
        return $methods;
    }

    /**
     * Extract the permission_callback from REST route args.
     */
    private function extractPermissionCallback(array $args): ?callable
    {
        if (isset($args['permission_callback']) && is_callable($args['permission_callback'])) {
            return $args['permission_callback'];
        }
        foreach ($args as $group) {
            if (is_array($group) && isset($group['permission_callback']) && is_callable($group['permission_callback'])) {
                return $group['permission_callback'];
            }
        }
        return null;
    }

    /**
     * Extract the main callback from REST route args.
     */
    private function extractCallback(array $args): ?callable
    {
        if (isset($args['callback']) && is_callable($args['callback'])) {
            return $args['callback'];
        }
        foreach ($args as $group) {
            if (is_array($group) && isset($group['callback']) && is_callable($group['callback'])) {
                return $group['callback'];
            }
        }
        return null;
    }
}
