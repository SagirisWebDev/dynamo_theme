<?php
declare(strict_types=1);

class Dynamo_Binding_Preview_Bridge {

    private Dynamo_Binding_Registry $registry;

    public function __construct(?Dynamo_Binding_Registry $registry) {
        $this->registry = $registry;
    }

    public function build_metadata(): array {
        $map = [];
        foreach ($this->registry->all() as $binding) {
            $entry = [
                'selector' => $binding['selector'],
                'property' => $binding['property'],
                'type'     => $binding['type'],
            ];
            if (isset($binding['unit']) && '' !== $binding['unit']) {
                $entry['unit'] = $binding['unit'];
            }
            if (isset($binding['choices']) && !empty($binding['choices'])) {
                $entry['choicesMap'] = self::choices_value_map($binding['choices']);
            }
            $map[$binding['setting_id']] = $entry;
        }
        return $map;
    }

    private static function choices_value_map(array $choices): array {
        $out = [];
        foreach ($choices as $slug => $entry) {
            if (is_array($entry) && isset($entry['value'])) {
                $out[$slug] = $entry['value'];
            }
        }
        return $out;
    }
}
