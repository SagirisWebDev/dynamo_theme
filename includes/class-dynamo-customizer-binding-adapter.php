<?php
declare(strict_types=1);

class Dynamo_Customizer_Binding_Adapter {

    private Dynamo_Binding_Registry $registry;

    public function __construct(Dynamo_Binding_Registry $registry) {
        $this->registry = $registry;
    }

    public function apply(object $wp_customize): void {
        $panels_created   = [];
        $sections_created = [];

        foreach ($this->registry->all() as $binding) {
            if (isset($binding['panel']) && '' !== $binding['panel']) {
                $panel_slug = $binding['panel'];
                if (!isset($panels_created[$panel_slug])) {
                    $wp_customize->add_panel($panel_slug, [
                        'title' => $binding['panel_label'] ?? self::derive_label($panel_slug),
                    ]);
                    $panels_created[$panel_slug] = true;
                }
            }

            $section_slug = $binding['section'];
            if (!isset($sections_created[$section_slug])) {
                $section_args = [
                    'title' => $binding['section_label'] ?? self::derive_label($section_slug),
                ];
                if (isset($binding['panel']) && '' !== $binding['panel']) {
                    $section_args['panel'] = $binding['panel'];
                }
                $wp_customize->add_section($section_slug, $section_args);
                $sections_created[$section_slug] = true;
            }

            $setting_id     = $binding['setting_id'];
            $setting_default = self::setting_default($binding);
            $wp_customize->add_setting($setting_id, [
                'default'           => $setting_default,
                'sanitize_callback' => $binding['sanitize_callback'],
                'transport'         => 'postMessage',
            ]);

            $control_args = [
                'label'   => $binding['label'],
                'section' => $section_slug,
            ];
            if (isset($binding['description'])) {
                $control_args['description'] = $binding['description'];
            }
            if (isset($binding['input_attrs'])) {
                $control_args['input_attrs'] = $binding['input_attrs'];
            }
            if (isset($binding['mime_type'])) {
                $control_args['mime_type'] = $binding['mime_type'];
            }
            if (isset($binding['code_type'])) {
                $control_args['code_type'] = $binding['code_type'];
            }

            $control_class = Dynamo_CSS_Vocabulary::control_class($binding['type']);
            if (null !== $control_class) {
                $control = new $control_class($wp_customize, $setting_id, $control_args);
            } else {
                $control_args['type'] = $binding['type'];
                if (isset($binding['choices'])) {
                    $control_args['choices'] = self::flatten_choices($binding['choices']);
                }
                $control = new WP_Customize_Control($wp_customize, $setting_id, $control_args);
            }

            $wp_customize->add_control($control);
        }
    }

    private static function derive_label(string $slug): string {
        return ucwords(str_replace(['_', '-'], ' ', $slug));
    }

    private static function setting_default(array $binding): mixed {
        $default = $binding['default'];
        if (($binding['type'] ?? '') !== 'code') {
            return $default;
        }
        if (is_string($default) && str_contains($default, '{')) {
            return $default;
        }
        return "{$binding['selector']} {\n    {$binding['property']}: {$default};\n}";
    }

    private static function flatten_choices(array $choices): array {
        $flat = [];
        foreach ($choices as $slug => $entry) {
            $flat[$slug] = is_array($entry) ? ($entry['label'] ?? $slug) : (string) $entry;
        }
        return $flat;
    }
}
