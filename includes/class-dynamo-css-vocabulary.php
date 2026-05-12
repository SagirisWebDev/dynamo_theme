<?php
declare(strict_types=1);

class Dynamo_CSS_Vocabulary {

    public static function type_categories(string $type): array {
        $map = self::type_category_map();
        return $map[$type] ?? [];
    }

    public static function property_categories(string $property): array {
        $map = self::property_map();
        return $map[$property] ?? [];
    }

    public static function is_unit(string $unit): bool {
        return in_array($unit, self::unit_list(), true);
    }

    public static function default_sanitizer(string $type): ?string {
        $map = [
            'color'    => 'sanitize_hex_color',
            'text'     => 'sanitize_text_field',
            'textarea' => 'sanitize_textarea_field',
            'number'   => 'floatval',
            'range'    => 'floatval',
            'url'      => 'esc_url_raw',
            'image'    => 'esc_url_raw',
            'media'    => 'absint',
            'date'     => 'sanitize_text_field',
            'code'     => 'wp_kses_post',
        ];
        return $map[$type] ?? null;
    }

    public static function default_value(string $type): mixed {
        $map = [
            'color'    => '#000000',
            'text'     => '',
            'textarea' => '',
            'number'   => 0,
            'range'    => 0,
            'url'      => '',
            'image'    => '',
            'media'    => 0,
            'date'     => '',
            'code'     => '',
        ];
        return $map[$type] ?? '';
    }

    public static function control_class(string $type): ?string {
        $map = [
            'color' => 'WP_Customize_Color_Control',
            'image' => 'WP_Customize_Image_Control',
            'media' => 'WP_Customize_Media_Control',
            'date'  => 'WP_Customize_Date_Time_Control',
            'code'  => 'WP_Customize_Code_Editor_Control',
        ];
        return $map[$type] ?? null;
    }

    private static function type_category_map(): array {
        return apply_filters('dynamo_binding_categories', [
            'color'    => ['color'],
            'text'     => ['string'],
            'textarea' => ['string'],
            'number'   => ['number'],
            'range'    => ['number'],
            'select'   => ['keyword'],
            'radio'    => ['keyword'],
            'url'      => ['url'],
            'image'    => ['url'],
            'media'    => ['url'],
            'date'     => ['string'],
            'code'     => ['string'],
        ]);
    }

    private static function property_map(): array {
        $color_props = [
            'color', 'background-color', 'border-color',
            'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color',
            'outline-color', 'caret-color', 'accent-color', 'fill', 'stroke',
        ];
        $map = [];
        foreach ($color_props as $p) {
            $map[$p] = ['color'];
        }
        return apply_filters('dynamo_binding_properties', $map);
    }

    private static function unit_list(): array {
        $units = [
            'px', 'cm', 'mm', 'in', 'pt', 'pc',
            'em', 'rem', 'ex', 'ch', 'vw', 'vh', 'vmin', 'vmax', '%', 'lh', 'rlh',
            'fr',
            's', 'ms',
            'deg', 'rad', 'grad', 'turn',
        ];
        return apply_filters('dynamo_binding_units', $units);
    }
}
