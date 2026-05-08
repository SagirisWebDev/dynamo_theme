<?php
declare(strict_types=1);

class Dynamo_CSS_Generator {

    private Dynamo_Token_Registry $registry;

    public function __construct(Dynamo_Token_Registry $registry) {
        $this->registry = $registry;
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
                $lines[] = "  --dynamo-{$key}: {$value};";
            }
        }
        return implode("\n", $lines);
    }
}
