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

    public function test_cache_key_includes_dynamo_version(): void {
        $cache = new Dynamo_CSS_Cache();
        $cache->set('css');
        $this->assertArrayHasKey('dynamo_css_' . DYNAMO_VERSION, $GLOBALS['wp_transients']);
    }
}
