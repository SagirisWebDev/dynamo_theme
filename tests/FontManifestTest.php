<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FontManifestTest extends TestCase {

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
}
