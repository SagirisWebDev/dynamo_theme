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
            if (empty($property_categories)) {
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

        return $errors;
    }
}
