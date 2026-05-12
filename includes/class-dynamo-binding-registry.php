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

        $this->bindings[$id] = $this->normalize($args);
    }

    public function all(): array {
        return $this->bindings;
    }

    public function get(string $id): ?array {
        return $this->bindings[$id] ?? null;
    }

    private function normalize(array $args): array {
        $type = $args['type'];

        if (!array_key_exists('default', $args)) {
            $args['default'] = Dynamo_CSS_Vocabulary::default_value($type);
        }

        if (!array_key_exists('sanitize_callback', $args)) {
            $args['sanitize_callback'] = Dynamo_CSS_Vocabulary::default_sanitizer($type);
        }

        $args['setting_id'] = 'dynamo_' . $args['id'];

        return $args;
    }
}
