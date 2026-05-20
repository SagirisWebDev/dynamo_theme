<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * TDD Red Phase — Issue #30: Consent Gate Block
 *
 * These tests are written BEFORE the implementation exists and are expected
 * to fail until the production code satisfies all acceptance criteria.
 *
 * Acceptance criteria covered:
 *
 *  AC1 — Block is registered under `blocks/consent-gate/` with a `block.json`,
 *         `render.php`, editor JS (index.js), and frontend JS (frontend.js).
 *
 *  AC4 — Server-side render outputs inner blocks wrapped in a container with
 *         `style="display:none"` and a `data-consent-category` attribute set
 *         to the selected slug.
 *
 *  AC8 — Unit test asserts server-side render outputs the hidden wrapper and
 *         correct `data-consent-category` value for a given block attribute.
 *
 * Note: AC2 (editor SelectControl), AC3 (inspector warning), AC5/AC6/AC7
 *       (frontend reveal) are exercised by the Playwright spec at
 *       tests/frontend/ui/consent-gate.spec.js.
 */

// ---------------------------------------------------------------------------
// Stub register_block_type() — not present in bootstrap.php. Records the
// arguments so tests can assert what was registered.
// ---------------------------------------------------------------------------
if (! function_exists('register_block_type')) {
    function register_block_type(string $block_type, array $args = []): mixed
    {
        $GLOBALS['wp_registered_blocks'][$block_type] = $args;
        return $args;
    }
}

class ConsentGateBlockTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Test lifecycle
    // -----------------------------------------------------------------------

    protected function setUp(): void
    {
        $GLOBALS['wp_filter']             = [];
        $GLOBALS['wp_registered_blocks']  = [];
        $GLOBALS['wp_doing_it_wrong']     = [];

        // The block files are loaded soft-mode so the tests fail at the
        // assertion level rather than with a fatal require error.
        $this->loadBlockFiles();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_registered_blocks']);
    }

    // -----------------------------------------------------------------------
    // AC1 — Block directory and required files exist
    // -----------------------------------------------------------------------

    /** @test */
    public function consent_gate_block_directory_exists(): void
    {
        $blockDir = DYNAMO_PATH . 'blocks/consent-gate';

        $this->assertDirectoryExists(
            $blockDir,
            "Block directory 'blocks/consent-gate/' must exist (AC1)."
        );
    }

    /** @test */
    public function consent_gate_block_json_file_exists(): void
    {
        $blockJsonPath = DYNAMO_PATH . 'blocks/consent-gate/block.json';

        $this->assertFileExists(
            $blockJsonPath,
            "blocks/consent-gate/block.json must exist (AC1)."
        );
    }

    /** @test */
    public function consent_gate_block_json_is_valid_json(): void
    {
        $blockJsonPath = DYNAMO_PATH . 'blocks/consent-gate/block.json';

        if (! is_file($blockJsonPath)) {
            $this->markTestSkipped('block.json does not exist yet — red phase.');
        }

        $decoded = json_decode((string) file_get_contents($blockJsonPath), true);

        $this->assertIsArray(
            $decoded,
            "blocks/consent-gate/block.json must contain valid JSON (AC1)."
        );
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            "blocks/consent-gate/block.json must be parseable JSON (AC1)."
        );
    }

    /** @test */
    public function consent_gate_block_json_declares_name(): void
    {
        $blockJsonPath = DYNAMO_PATH . 'blocks/consent-gate/block.json';

        if (! is_file($blockJsonPath)) {
            $this->markTestSkipped('block.json does not exist yet — red phase.');
        }

        $decoded = json_decode((string) file_get_contents($blockJsonPath), true);

        $this->assertIsArray($decoded, 'block.json must decode to an array.');
        $this->assertArrayHasKey('name', $decoded, "block.json must declare a 'name'.");
        $this->assertStringContainsString(
            'consent-gate',
            (string) $decoded['name'],
            "block.json 'name' must reference 'consent-gate'."
        );
    }

    /** @test */
    public function consent_gate_block_json_declares_consent_category_attribute(): void
    {
        $blockJsonPath = DYNAMO_PATH . 'blocks/consent-gate/block.json';

        if (! is_file($blockJsonPath)) {
            $this->markTestSkipped('block.json does not exist yet — red phase.');
        }

        $decoded = json_decode((string) file_get_contents($blockJsonPath), true);

        $this->assertIsArray($decoded, 'block.json must decode to an array.');
        $this->assertArrayHasKey(
            'attributes',
            $decoded,
            "block.json must declare attributes."
        );
        $this->assertArrayHasKey(
            'consentCategory',
            $decoded['attributes'],
            "block.json attributes must declare 'consentCategory' (AC2/AC4)."
        );
    }

    /** @test */
    public function consent_gate_render_php_file_exists(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        $this->assertFileExists(
            $renderPath,
            "blocks/consent-gate/render.php must exist (AC1)."
        );
    }

    /** @test */
    public function consent_gate_editor_js_file_exists(): void
    {
        // Editor JS may be either an unbundled source (index.js) or the
        // compiled WP scripts output (build/index.js). Accept either form.
        $sourceJs   = DYNAMO_PATH . 'blocks/consent-gate/index.js';
        $compiledJs = DYNAMO_PATH . 'blocks/consent-gate/build/index.js';

        $this->assertTrue(
            is_file($sourceJs) || is_file($compiledJs),
            "An editor JS file must exist at blocks/consent-gate/index.js or blocks/consent-gate/build/index.js (AC1)."
        );
    }

    /** @test */
    public function consent_gate_frontend_js_file_exists(): void
    {
        $frontendPath = DYNAMO_PATH . 'blocks/consent-gate/frontend.js';

        $this->assertFileExists(
            $frontendPath,
            "blocks/consent-gate/frontend.js must exist (AC1)."
        );
    }

    // -----------------------------------------------------------------------
    // AC4 / AC8 — Server-side render outputs the hidden wrapper with
    // data-consent-category attribute set to the selected slug.
    // -----------------------------------------------------------------------

    /** @test */
    public function render_php_outputs_dynamo_consent_gate_wrapper(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        $attributes = ['consentCategory' => 'marketing'];
        $content    = '<p>Inner block content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertStringContainsString(
            'dynamo-consent-gate',
            $output,
            "render.php must output a wrapper element with the 'dynamo-consent-gate' class (AC4)."
        );
    }

    /** @test */
    public function render_php_outputs_display_none_style(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        $attributes = ['consentCategory' => 'marketing'];
        $content    = '<p>Inner block content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        // Allow any whitespace variations in the style attribute.
        $this->assertMatchesRegularExpression(
            '/style="[^"]*display\s*:\s*none[^"]*"/i',
            $output,
            "render.php must output the wrapper with style=\"display:none\" (AC4/AC8)."
        );
    }

    /** @test */
    public function render_php_outputs_data_consent_category_attribute_with_selected_slug(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        $attributes = ['consentCategory' => 'marketing'];
        $content    = '<p>Inner block content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertMatchesRegularExpression(
            '/data-consent-category="marketing"/i',
            $output,
            "render.php must output data-consent-category=\"marketing\" (AC4/AC8)."
        );
    }

    /** @test */
    public function render_php_uses_selected_category_from_block_attributes(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        // AC8 explicitly: assert the wrapper reflects the attribute value.
        $attributes = ['consentCategory' => 'statistics'];
        $content    = '<p>Statistics-dependent content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertMatchesRegularExpression(
            '/data-consent-category="statistics"/i',
            $output,
            "render.php must reflect the consentCategory attribute in data-consent-category (AC8)."
        );

        // And must not leak any other category slug.
        $this->assertStringNotContainsString(
            'data-consent-category="marketing"',
            $output,
            "render.php must not output the wrong category."
        );
    }

    /** @test */
    public function render_php_emits_inner_blocks_content_inside_wrapper(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        $attributes = ['consentCategory' => 'marketing'];
        $content    = '<p id="inner-paragraph">Hidden inner content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertStringContainsString(
            'Hidden inner content',
            $output,
            "render.php must include the inner blocks content within the wrapper (AC4)."
        );
        $this->assertStringContainsString(
            '<p id="inner-paragraph">',
            $output,
            "render.php must emit the inner blocks HTML verbatim (AC4)."
        );
    }

    /** @test */
    public function render_php_inner_content_is_nested_inside_hidden_wrapper(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        $attributes = ['consentCategory' => 'marketing'];
        $content    = '<p>UNIQUE_INNER_CONTENT_TOKEN</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        // Confirm the inner content appears AFTER the opening wrapper tag and
        // BEFORE the closing tag — i.e. it really is nested.
        $this->assertMatchesRegularExpression(
            '/<div[^>]*data-consent-category="marketing"[^>]*>.*UNIQUE_INNER_CONTENT_TOKEN.*<\/div>/is',
            $output,
            "Inner blocks HTML must be nested inside the hidden wrapper (AC4)."
        );
    }

    /** @test */
    public function render_php_handles_empty_consent_category_gracefully(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        // No consentCategory attribute → must not produce a fatal error and
        // must still output a wrapper (even if data-consent-category is empty).
        $attributes = [];
        $content    = '<p>Content</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertStringContainsString(
            'dynamo-consent-gate',
            $output,
            "render.php must still emit the wrapper when consentCategory is unset."
        );
        $this->assertStringContainsString(
            'data-consent-category=',
            $output,
            "render.php must always emit the data-consent-category attribute."
        );
    }

    /** @test */
    public function render_php_escapes_consent_category_attribute_value(): void
    {
        $renderPath = DYNAMO_PATH . 'blocks/consent-gate/render.php';

        if (! is_file($renderPath)) {
            $this->markTestSkipped('render.php does not exist yet — red phase.');
        }

        // Attempted XSS payload in the attribute.
        $attributes = ['consentCategory' => 'marketing"><script>alert(1)</script>'];
        $content    = '<p>Inner</p>';
        $block      = null;

        $output = $this->renderBlock($renderPath, $attributes, $content, $block);

        $this->assertStringNotContainsString(
            '<script>alert(1)</script>',
            $output,
            "consentCategory must be escaped — raw <script> tag must not appear in the output."
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Soft-load block files. Files may not exist during the red phase; tests
     * that need them check is_file() themselves.
     */
    private function loadBlockFiles(): void
    {
        // The render.php template is not autoloaded — it's included directly
        // by the render() helper below when present.
        $files = [
            DYNAMO_PATH . 'includes/blocks/class-dynamo-consent-gate-block.php',
        ];
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Execute the block render.php with the given attributes / content. This
     * mimics how WordPress invokes a `render` callback: $attributes, $content,
     * and $block are exposed in the file's scope (matching the WP convention
     * used by render_callback() in block.json).
     */
    private function renderBlock(string $renderPath, array $attributes, string $content, mixed $block): string
    {
        ob_start();
        // These variable names are the contract WordPress passes to a
        // block's render.php when registered via block.json `render` key.
        (static function () use ($renderPath, $attributes, $content, $block): void {
            include $renderPath;
        })();
        return (string) ob_get_clean();
    }
}
