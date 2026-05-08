<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ThemeJSONSyncTest extends TestCase {

    private Dynamo_Token_Registry $registry;
    private Dynamo_Theme_JSON_Sync $sync;

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
        $this->registry = new Dynamo_Token_Registry();
        $this->sync     = new Dynamo_Theme_JSON_Sync($this->registry);
        $this->sync->init();
    }

    private function applyFilter(array $data): array {
        $theme_json = new WP_Theme_JSON_Data($data);
        $result     = apply_filters('wp_theme_json_data_theme', $theme_json);
        return $result->get_data();
    }

    // --- Slice 1: tracer bullet — primary colour in palette ---

    public function test_primary_colour_injected_with_correct_slug(): void {
        $data = $this->applyFilter([]);

        $palette = $data['settings']['color']['palette'] ?? [];
        $slugs   = array_column($palette, 'slug');

        $this->assertContains('dynamo-primary', $slugs);
    }

    public function test_primary_colour_has_correct_value(): void {
        $data    = $this->applyFilter([]);
        $palette = $data['settings']['color']['palette'] ?? [];

        $entry = array_filter($palette, fn($e) => $e['slug'] === 'dynamo-primary');
        $entry = array_values($entry)[0] ?? null;

        $this->assertNotNull($entry);
        $this->assertSame($this->registry->get('colors-primary'), $entry['color']);
    }

    // --- Slice 2: all 7 colour tokens injected ---

    public function test_all_seven_colour_slugs_are_injected(): void {
        $data    = $this->applyFilter([]);
        $palette = $data['settings']['color']['palette'] ?? [];
        $slugs   = array_column($palette, 'slug');

        $expected = [
            'dynamo-primary',
            'dynamo-secondary',
            'dynamo-accent',
            'dynamo-background',
            'dynamo-text',
            'dynamo-link',
            'dynamo-section-alt',
        ];

        foreach ($expected as $slug) {
            $this->assertContains($slug, $slugs, "Missing colour slug: $slug");
        }
    }

    // --- Slice 3: existing non-Dynamo palette entries preserved ---

    public function test_existing_non_dynamo_palette_entries_preserved(): void {
        $existing = [
            'settings' => [
                'color' => [
                    'palette' => [
                        ['slug' => 'brand-red', 'color' => '#ff0000', 'name' => 'Brand Red'],
                    ],
                ],
            ],
        ];

        $data    = $this->applyFilter($existing);
        $palette = $data['settings']['color']['palette'] ?? [];
        $slugs   = array_column($palette, 'slug');

        $this->assertContains('brand-red', $slugs);
    }

    // --- Slice 4: body font family preset injected ---

    public function test_body_font_family_preset_injected(): void {
        $data         = $this->applyFilter([]);
        $fontFamilies = $data['settings']['typography']['fontFamilies'] ?? [];
        $slugs        = array_column($fontFamilies, 'slug');

        $this->assertContains('dynamo-body', $slugs);
    }

    public function test_body_font_family_preset_has_correct_value(): void {
        $data         = $this->applyFilter([]);
        $fontFamilies = $data['settings']['typography']['fontFamilies'] ?? [];

        $entry = array_filter($fontFamilies, fn($e) => $e['slug'] === 'dynamo-body');
        $entry = array_values($entry)[0] ?? null;

        $this->assertNotNull($entry);
        $this->assertSame($this->registry->get('typography-body-font-family'), $entry['fontFamily']);
    }

    // --- Slice 5: body font size preset injected ---

    public function test_body_font_size_preset_injected(): void {
        $data      = $this->applyFilter([]);
        $fontSizes = $data['settings']['typography']['fontSizes'] ?? [];
        $slugs     = array_column($fontSizes, 'slug');

        $this->assertContains('dynamo-body', $slugs);
    }

    public function test_body_font_size_preset_has_correct_value(): void {
        $data      = $this->applyFilter([]);
        $fontSizes = $data['settings']['typography']['fontSizes'] ?? [];

        $entry = array_filter($fontSizes, fn($e) => $e['slug'] === 'dynamo-body');
        $entry = array_values($entry)[0] ?? null;

        $this->assertNotNull($entry);
        $this->assertSame($this->registry->get('typography-body-font-size'), $entry['size']);
    }

    // --- Slice 6: existing non-Dynamo typography entries preserved ---

    public function test_existing_non_dynamo_font_families_preserved(): void {
        $existing = [
            'settings' => [
                'typography' => [
                    'fontFamilies' => [
                        ['slug' => 'brand-serif', 'fontFamily' => 'Georgia', 'name' => 'Brand Serif'],
                    ],
                ],
            ],
        ];

        $data         = $this->applyFilter($existing);
        $fontFamilies = $data['settings']['typography']['fontFamilies'] ?? [];
        $slugs        = array_column($fontFamilies, 'slug');

        $this->assertContains('brand-serif', $slugs);
    }

    // --- Slice 7: idempotency ---

    public function test_applying_filter_twice_is_idempotent(): void {
        $first  = $this->applyFilter([]);
        $second = $this->applyFilter($first);

        $this->assertSame(
            array_column($first['settings']['color']['palette'] ?? [], 'slug'),
            array_column($second['settings']['color']['palette'] ?? [], 'slug')
        );

        $this->assertSame(
            array_column($first['settings']['typography']['fontFamilies'] ?? [], 'slug'),
            array_column($second['settings']['typography']['fontFamilies'] ?? [], 'slug')
        );
    }
}
