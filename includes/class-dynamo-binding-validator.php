<?php
declare(strict_types=1);

class Dynamo_Binding_Validator {

    private const REQUIRED = ['id', 'type', 'label', 'section', 'selector', 'property'];

    public function validate(array $args): array {
        $errors = [];

        foreach (self::REQUIRED as $key) {
            if (!isset($args[$key]) || '' === $args[$key]) {
                $errors[] = "Missing required field: {$key}";
            }
        }

        $type     = $args['type']     ?? null;
        $property = $args['property'] ?? null;

        if (null !== $type) {
            $type_categories = Dynamo_CSS_Vocabulary::type_categories($type);
            if (empty($type_categories)) {
                $errors[] = "Unknown type: {$type}";
            }
            // number/range with a unit produce [number, length] per PRD.
            if (in_array($type, ['number', 'range'], true)
                && isset($args['unit']) && '' !== $args['unit']
                && Dynamo_CSS_Vocabulary::is_unit((string) $args['unit'])
                && !in_array('length', $type_categories, true)
            ) {
                $type_categories[] = 'length';
            }
        } else {
            $type_categories = [];
        }

        if (null !== $property) {
            $property_categories = Dynamo_CSS_Vocabulary::property_categories($property);
            if (empty($property_categories) && !Dynamo_CSS_Vocabulary::has_parent_requirement($property)) {
                $errors[] = "Unknown property: {$property}";
            }
        } else {
            $property_categories = [];
        }

        if (!empty($type_categories) && !empty($property_categories)) {
            $intersection = array_intersect($type_categories, $property_categories);
            if (empty($intersection) && !in_array('any', $property_categories, true)) {
                $errors[] = "Incompatible type '{$type}' and property '{$property}': no shared value category";
            }
        }

        if (isset($args['unit']) && '' !== $args['unit']) {
            if (!Dynamo_CSS_Vocabulary::is_unit((string) $args['unit'])) {
                $errors[] = "Unknown unit: {$args['unit']}";
            }
        }

        if (in_array($type, ['radio', 'select'], true)) {
            $errors = [...$errors, ...$this->validate_choices($args)];
        }

        if (null !== $property) {
            $errors = [...$errors, ...$this->validate_requirements($args, $property)];
        }

        return $errors;
    }

    private function validate_requirements(array $args, string $property): array {
        if (Dynamo_CSS_Vocabulary::has_parent_requirement($property)) {
            return [
                "Property '{$property}' requires a declaration on the parent selector — not handled automatically by dynamo_config_customizer. Configure the parent's display/position in your theme CSS, then drop the parent-only property from this binding.",
            ];
        }

        $required = Dynamo_CSS_Vocabulary::property_requirement($property);
        if (null === $required) {
            return [];
        }

        $selector = $args['selector'] ?? '';
        [$req_prop, $req_value] = [array_key_first($required), reset($required)];
        $supplied = $args['requires'] ?? null;

        if (null === $supplied) {
            return [
                "Property '{$property}' requires `{$req_prop}: {$req_value}` on `{$selector}`. Either set this in your theme CSS, or pass 'requires' => ['{$req_prop}' => '{$req_value}'] to have the binding emit it.",
            ];
        }

        if (!is_array($supplied) || !array_key_exists($req_prop, $supplied)) {
            return [
                "Property '{$property}' requires `{$req_prop}: {$req_value}`, but 'requires' does not declare '{$req_prop}'.",
            ];
        }

        if ($supplied[$req_prop] !== $req_value) {
            return [
                "Property '{$property}' requires `{$req_prop}: {$req_value}`, but 'requires' declares `{$req_prop}: {$supplied[$req_prop]}`.",
            ];
        }

        return [];
    }

    private function validate_choices(array $args): array {
        $errors = [];

        if (!isset($args['choices']) || !is_array($args['choices']) || empty($args['choices'])) {
            $errors[] = "Type '{$args['type']}' requires non-empty 'choices' array";
            return $errors;
        }

        foreach ($args['choices'] as $slug => $entry) {
            if (!is_array($entry)) {
                $errors[] = "choices['{$slug}'] must be ['label' => ..., 'value' => ...]; flat slug=>label form is not supported";
                return $errors;
            }
            if (!isset($entry['label']) || '' === $entry['label']) {
                $errors[] = "choices['{$slug}'] missing 'label' key";
            }
            if (!isset($entry['value']) || '' === $entry['value']) {
                $errors[] = "choices['{$slug}'] missing 'value' key";
            }
        }

        if (isset($args['default']) && '' !== $args['default']
            && !array_key_exists($args['default'], $args['choices'])
        ) {
            $errors[] = "default '{$args['default']}' is not a key in choices";
        }

        return $errors;
    }
}
