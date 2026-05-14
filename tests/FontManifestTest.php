<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FontManifestTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_current_user_can'] = [];
    }

    private function fixture(string $name): string {
        return __DIR__ . "/fixtures/font-manifest/{$name}.json";
    }

    public function test_all_returns_entries_keyed_by_slug(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $entries  = $manifest->all();
        $this->assertArrayHasKey('system-sans', $entries);
        $this->assertArrayHasKey('inter', $entries);
    }

    public function test_get_returns_the_entry_for_a_known_slug(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $entry    = $manifest->get('inter');
        $this->assertIsArray($entry);
        $this->assertSame('Inter', $entry['label']);
        $this->assertSame('sans-serif', $entry['fallback']);
    }

    public function test_get_returns_null_for_unknown_slug(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $this->assertNull($manifest->get('does-not-exist'));
    }

    public function test_has_returns_true_for_known_slug(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $this->assertTrue($manifest->has('system-sans'));
    }

    public function test_has_returns_false_for_unknown_slug(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $this->assertFalse($manifest->has('does-not-exist'));
    }

    public function test_is_valid_returns_true_for_well_formed_manifest(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        $this->assertTrue($manifest->is_valid());
        $this->assertSame([], $manifest->get_errors());
    }

    public function test_malformed_json_is_invalid_and_falls_back_to_safety_default(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('malformed'));
        $this->assertFalse($manifest->is_valid());
        $this->assertArrayHasKey('system-sans', $manifest->all());
        $errors = $manifest->get_errors();
        $this->assertStringContainsString('malformed', strtolower(implode(' ', $errors)));
    }

    public function test_missing_file_is_invalid_and_falls_back_to_safety_default(): void {
        $manifest = new Dynamo_Font_Manifest('/nonexistent/path/fonts.json');
        $this->assertFalse($manifest->is_valid());
        $this->assertArrayHasKey('system-sans', $manifest->all());
        $errors = $manifest->get_errors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('not found', strtolower(implode(' ', $errors)));
    }

    public function test_entries_missing_required_fields_are_dropped_and_recorded(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('missing-fields'));
        $entries  = $manifest->all();
        $this->assertArrayHasKey('complete', $entries);
        $this->assertArrayNotHasKey('no-label', $entries);
        $this->assertArrayNotHasKey('no-fallback', $entries);
        $this->assertFalse($manifest->is_valid());
        $errors = implode(' ', $manifest->get_errors());
        $this->assertStringContainsString('no-label', $errors);
        $this->assertStringContainsString('no-fallback', $errors);
    }

    public function test_entries_with_invalid_slug_format_are_dropped_and_recorded(): void {
        $manifest = new Dynamo_Font_Manifest($this->fixture('invalid-slug'));
        $entries  = $manifest->all();
        $this->assertArrayHasKey('valid-slug', $entries);
        $this->assertArrayNotHasKey('Invalid Slug', $entries);
        $this->assertArrayNotHasKey('Under_score', $entries);
        $this->assertFalse($manifest->is_valid());
        $errors = implode(' ', $manifest->get_errors());
        $this->assertStringContainsString('Invalid Slug', $errors);
        $this->assertStringContainsString('Under_score', $errors);
    }

    public function test_render_admin_notice_outputs_nothing_for_valid_manifest(): void {
        $GLOBALS['wp_current_user_can']['manage_options'] = true;
        $manifest = new Dynamo_Font_Manifest($this->fixture('valid'));
        ob_start();
        $manifest->render_admin_notice();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_admin_notice_outputs_nothing_when_user_lacks_capability(): void {
        $GLOBALS['wp_current_user_can']['manage_options'] = false;
        $manifest = new Dynamo_Font_Manifest($this->fixture('malformed'));
        ob_start();
        $manifest->render_admin_notice();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_admin_notice_outputs_dismissible_notice_with_errors_for_capable_user(): void {
        $GLOBALS['wp_current_user_can']['manage_options'] = true;
        $manifest = new Dynamo_Font_Manifest($this->fixture('malformed'));
        ob_start();
        $manifest->render_admin_notice();
        $output = ob_get_clean();
        $this->assertStringContainsString('notice', $output);
        $this->assertStringContainsString('is-dismissible', $output);
        $this->assertStringContainsString('malformed', strtolower($output));
    }
}
