<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for Issue #28: Cookie Categories — Driver method contracts.
 *
 * Covers AC1–AC3: both drivers implement get_consent_categories() returning
 * the correct shape. REST endpoint registration (AC4–AC6) moved to the
 * dynamo-consent-gate plugin.
 */

class CookieCategoriesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Test lifecycle
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']         = [];
        $GLOBALS['wp_doing_it_wrong'] = [];

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
    // Helpers
    // -----------------------------------------------------------------------

    private function loadCookieFiles(): void
    {
        $files = [
            DYNAMO_PATH . 'includes/cookie/interface-dynamo-cookie-driver.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-complianz.php',
            DYNAMO_PATH . 'includes/cookie/class-dynamo-cookie-driver-borlabs.php',
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
}
