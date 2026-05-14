<?php
declare(strict_types=1);

class Dynamo_Binding_Registry {

    private static ?Dynamo_Binding_Registry $instance = null;

    private array $bindings = [];
    private Dynamo_Binding_Validator $validator;

    public function __construct(?Dynamo_Binding_Validator $validator = null) {
        $this->validator = $validator ?? new Dynamo_Binding_Validator();
    }

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset_instance(): void {
        self::$instance = null;
    }

    public function register(array $args): void {
        $errors = $this->validator->validate($args);
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Invalid Binding registration: ' . implode('; ', $errors)
            );
        }

        $id = $args['id'];
        if (isset($this->bindings[$id])) {
            throw new InvalidArgumentException("Duplicate Binding id: {$id}");
        }

        $this->detect_requires_conflict($args);

        $this->bindings[$id] = $this->normalize($args);
    }

    private function detect_requires_conflict(array $args): void {
        if (empty($args['requires']) || !is_array($args['requires'])) {
            return;
        }
        $selector = $args['selector'];
        foreach ($this->bindings as $existing) {
            if ($existing['selector'] !== $selector || empty($existing['requires'])) {
                continue;
            }
            foreach ($args['requires'] as $prop => $value) {
                if (isset($existing['requires'][$prop]) && $existing['requires'][$prop] !== $value) {
                    throw new InvalidArgumentException(
                        "Conflicting requires on selector '{$selector}': binding '{$args['id']}' wants `{$prop}: {$value}`, "
                        . "but binding '{$existing['id']}' already requires `{$prop}: {$existing['requires'][$prop]}`."
                    );
                }
            }
        }
    }

    public function all(): array {
        return $this->bindings;
    }

    public function get(string $id): ?array {
        return $this->bindings[$id] ?? null;
    }

    private function normalize(array $args): array {
        $type        = $args['type'];
        $has_choices = in_array($type, ['radio', 'select'], true) && !empty($args['choices']);

        if (!array_key_exists('default', $args)) {
            $args['default'] = $has_choices
                ? (string) array_key_first($args['choices'])
                : Dynamo_CSS_Vocabulary::default_value($type);
        }

        if (!array_key_exists('sanitize_callback', $args)) {
            if ($has_choices) {
                $valid   = array_keys($args['choices']);
                $default = $args['default'];
                $args['sanitize_callback'] = static fn($value) => in_array($value, $valid, true) ? $value : $default;
            } else {
                $args['sanitize_callback'] = Dynamo_CSS_Vocabulary::default_sanitizer($type);
            }
        }

        $args['setting_id'] = 'dynamo_' . $args['id'];

        return $args;
    }
}
