<?php
declare(strict_types=1);

class Dynamo_CSS_Generator {

    private Dynamo_Token_Registry $registry;
    private Dynamo_Font_Manifest $fonts;

    public function __construct(Dynamo_Token_Registry $registry, Dynamo_Font_Manifest $fonts) {
        $this->registry = $registry;
        $this->fonts    = $fonts;
    }

    public function generate(): string {
        $modules = apply_filters('dynamo_css_modules', ['colors', 'typography', 'spacing', 'layout', 'borders', 'shadows']);

        if (empty($modules)) {
            return '';
        }

        $parts = [];
        foreach ($modules as $module) {
            $declarations = $this->module_declarations($module);
            $declarations = apply_filters("dynamo_css_{$module}", $declarations, $this->registry);
            if ('' !== $declarations) {
                $parts[] = $declarations;
            }
        }

        if (empty($parts)) {
            return '';
        }

        return ":root {\n" . implode("\n", $parts) . "\n}";
    }

    private function module_declarations(string $module): string {
        $lines = [];
        foreach ($this->registry->all() as $key => $value) {
            if (str_starts_with($key, $module)) {
                $setting_id = 'dynamo_' . str_replace('-', '_', $key);
                $saved      = get_theme_mod($setting_id);
                if ('' !== $saved && false !== $saved) {
                    $value = $saved;
                }
                if (str_ends_with($key, '-font-family')) {
                    $value = $this->resolve_font_family((string) $value);
                }
                $lines[] = "  --dynamo-{$key}: {$value};";
            }
        }
        return implode("\n", $lines);
    }

    private function resolve_font_family(string $slug): string {
        $entry = $this->fonts->get($slug);
        if ($entry === null) {
            // Unknown-slug handling stubbed for slice 1; hardened in slice 2.
            return 'sans-serif';
        }
        $fallback = $entry['fallback'] ?? 'sans-serif';
        $faces    = $entry['faces'] ?? [];
        if (empty($faces)) {
            return $fallback;
        }
        $label = $entry['label'] ?? $slug;
        return '"' . $label . '", ' . $fallback;
    }
}
