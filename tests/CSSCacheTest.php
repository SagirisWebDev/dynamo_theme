<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CSSCacheTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_transients'] = [];
    }

    public function test_get_returns_null_on_cold_cache(): void {
        $this->assertNull((new Dynamo_CSS_Cache())->get());
    }

    public function test_get_returns_stored_string_after_set(): void {
        $cache = new Dynamo_CSS_Cache();
        $css   = ':root { --dynamo-colors-primary: #3b82f6; }';
        $cache->set($css);
        $this->assertSame($css, $cache->get());
    }

    public function test_get_returns_null_after_bust(): void {
        $cache = new Dynamo_CSS_Cache();
        $cache->set('some css');
        $cache->bust();
        $this->assertNull($cache->get());
    }

    public function test_cache_key_includes_dynamo_version_and_style_mtime(): void {
        $cache = new Dynamo_CSS_Cache();
        $cache->set('css');
        $mtime = (int) @filemtime(DYNAMO_PATH . 'assets/css/style.css');
        $this->assertArrayHasKey('dynamo_css_' . DYNAMO_VERSION . '_' . $mtime, $GLOBALS['wp_transients']);
    }

    public function test_cache_key_changes_when_style_css_changes(): void {
        $cache = new Dynamo_CSS_Cache();
        $cache->set('original');

        // Simulate a style.css edit by touching the file forward in time.
        $path     = DYNAMO_PATH . 'assets/css/style.css';
        $original = (int) @filemtime($path);
        touch($path, $original + 60);

        try {
            $this->assertNull($cache->get(), 'Stale entry should not be returned after style.css mtime changes');
        } finally {
            touch($path, $original);
        }
    }
}
