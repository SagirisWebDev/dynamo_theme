<?php
declare(strict_types=1);

class Dynamo_Binding_CSS_Renderer {

    private Dynamo_Binding_Registry $registry;

    public function __construct(Dynamo_Binding_Registry $registry) {
        $this->registry = $registry;
    }

    public function variable_lines(): array {
        $lines = [];
        foreach ($this->registry->all() as $binding) {
            $value = $this->resolve_value($binding);
            $lines[] = "  --dynamo-{$binding['id']}: {$value};";
        }
        return $lines;
    }

    public function rule_lines(): array {
        $lines    = [];
        $emitted  = [];
        foreach ($this->registry->all() as $binding) {
            if (!empty($binding['requires']) && is_array($binding['requires'])) {
                foreach ($binding['requires'] as $req_prop => $req_value) {
                    $signature = $binding['selector'] . '|' . $req_prop . ':' . $req_value;
                    if (isset($emitted[$signature])) {
                        continue;
                    }
                    $emitted[$signature] = true;
                    $lines[] = "{$binding['selector']} { {$req_prop}: {$req_value}; }";
                }
            }

            $lines[] = "{$binding['selector']} { {$binding['property']}: var(--dynamo-{$binding['id']}); }";
        }
        return $lines;
    }

    public function extras_blocks(): array {
        $out = [];
        foreach ($this->registry->all() as $binding) {
            if (($binding['type'] ?? '') !== 'code') {
                continue;
            }
            $extras = $this->code_extra_decls($binding);
            if (empty($extras)) {
                continue;
            }
            $parts = array_map(static fn($d) => "{$d[0]}: {$d[1]};", $extras);
            $out[$binding['id']] = "{$binding['selector']} { " . implode(' ', $parts) . " }";
        }
        return $out;
    }

    public function render(): string {
        $vars  = $this->variable_lines();
        $rules = $this->rule_lines();
        if (empty($vars) && empty($rules)) {
            return '';
        }
        $root = ":root {\n" . implode("\n", $vars) . "\n}";
        return $root . "\n" . implode("\n", $rules);
    }

    private function resolve_value(array $binding): string {
        $saved = get_theme_mod($binding['setting_id']);
        if ('' !== $saved && false !== $saved && null !== $saved) {
            $value = (string) $saved;
        } else {
            $value = (string) $binding['default'];
        }
        $type = $binding['type'] ?? '';
        if ('code' === $type && str_contains($value, '{')) {
            $bound = self::find_bound_value_in_block($value, $binding['property']);
            $value = null !== $bound ? $bound : (string) $binding['default'];
        }
        if ('media' === $type) {
            $value = self::resolve_attachment($value);
        }
        if (in_array($type, ['url', 'image', 'media'], true) && '' !== $value) {
            $value = self::wrap_url($value);
        }
        if (!empty($binding['choices']) && is_array($binding['choices'])) {
            $value = self::resolve_choice($value, $binding);
        }
        if (($binding['property'] ?? '') === 'content' && '' !== $value) {
            $value = self::wrap_content_string($value);
        }
        return self::apply_unit($value, $binding);
    }

    private static function wrap_content_string(string $value): string {
        $trimmed = ltrim($value);
        // Already a CSS string literal.
        if (str_starts_with($trimmed, '"') || str_starts_with($trimmed, "'")) {
            return $value;
        }
        // CSS function notation (url(), counter(), counters(), attr(), var(), linear-gradient(), etc.)
        if (1 === preg_match('/^[a-zA-Z-]+\(/', $trimmed)) {
            return $value;
        }
        // Plain CSS-wide keyword.
        if (in_array(strtolower($trimmed), ['none', 'normal', 'initial', 'inherit', 'unset', 'revert', 'revert-layer'], true)) {
            return $value;
        }
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private static function wrap_url(string $value): string {
        if (str_starts_with($value, 'url(')) {
            return $value;
        }
        return "url('{$value}')";
    }

    private static function resolve_attachment(string $value): string {
        $id = (int) $value;
        if ($id <= 0) {
            return '';
        }
        $url = wp_get_attachment_url($id);
        return false === $url ? '' : (string) $url;
    }

    private static function resolve_choice(string $slug, array $binding): string {
        $choices = $binding['choices'];
        if (isset($choices[$slug]['value'])) {
            return (string) $choices[$slug]['value'];
        }
        $default_slug = (string) $binding['default'];
        if (isset($choices[$default_slug]['value'])) {
            return (string) $choices[$default_slug]['value'];
        }
        return $slug;
    }

    private function code_extra_decls(array $binding): array {
        $saved = get_theme_mod($binding['setting_id']);
        $value = ('' !== $saved && false !== $saved && null !== $saved) ? (string) $saved : (string) $binding['default'];
        if (!str_contains($value, '{')) {
            return [];
        }
        $decls = self::parse_block_declarations($value);
        $bound = $binding['property'];
        $out   = [];
        foreach ($decls as [$prop, $val]) {
            if ($prop !== $bound) {
                $out[] = [$prop, $val];
            }
        }
        return $out;
    }

    private static function find_bound_value_in_block(string $css, string $bound_property): ?string {
        foreach (self::parse_block_declarations($css) as [$prop, $value]) {
            if ($prop === $bound_property) {
                return $value;
            }
        }
        return null;
    }

    private static function parse_block_declarations(string $css): array {
        if (1 !== preg_match('/\{(.*)\}/s', $css, $m)) {
            return [];
        }
        $block = $m[1];
        $out   = [];
        foreach (explode(';', $block) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            $colon = strpos($line, ':');
            if (false === $colon) {
                continue;
            }
            $prop  = trim(substr($line, 0, $colon));
            $value = trim(substr($line, $colon + 1));
            if ('' !== $prop && '' !== $value) {
                $out[] = [$prop, $value];
            }
        }
        return $out;
    }

    private static function apply_unit(string $value, array $binding): string {
        $unit = $binding['unit'] ?? '';
        if ('' === $unit || '' === $value) {
            return $value;
        }
        if (str_ends_with($value, $unit)) {
            return $value;
        }
        return $value . $unit;
    }
}
