<?php
declare(strict_types=1);

class Dynamo_Binding_CSS_Renderer {

    private Dynamo_Binding_Registry $registry;

    public function __construct(Dynamo_Binding_Registry $registry) {
        $this->registry = $registry;
    }

    public function render(): string {
        $bindings = $this->registry->all();
        if (empty($bindings)) {
            return '';
        }

        $variable_lines = [];
        $rule_lines     = [];

        foreach ($bindings as $binding) {
            $id       = $binding['id'];
            $value    = $this->resolve_value($binding);
            $selector = $binding['selector'];
            $property = $binding['property'];

            $variable_lines[] = "  --dynamo-{$id}: {$value};";
            $rule_lines[]     = "{$selector} { {$property}: var(--dynamo-{$id}); }";
        }

        $root = ":root {\n" . implode("\n", $variable_lines) . "\n}";
        $rules = implode("\n", $rule_lines);

        return $root . "\n" . $rules;
    }

    private function resolve_value(array $binding): string {
        $saved = get_theme_mod($binding['setting_id']);
        if ('' !== $saved && false !== $saved && null !== $saved) {
            return (string) $saved;
        }
        return (string) $binding['default'];
    }
}
