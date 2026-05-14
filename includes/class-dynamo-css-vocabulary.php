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

    public static function property_requirement(string $property): ?array {
        $map = self::requirement_map();
        return $map[$property] ?? null;
    }

    public static function has_parent_requirement(string $property): bool {
        return in_array($property, self::parent_requirement_list(), true);
    }

    public static function default_sanitizer(string $type): callable|string|null {
        $map = [
            'color'    => 'sanitize_hex_color',
            'text'     => 'sanitize_text_field',
            'textarea' => 'sanitize_textarea_field',
            'number'   => static fn($value) => floatval($value),
            'range'    => static fn($value) => floatval($value),
            'url'      => 'esc_url_raw',
            'image'    => 'esc_url_raw',
            'media'    => static fn($value) => absint($value),
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
        $length_keyword_props = [
            'width', 'height', 'min-width', 'min-height', 'max-width', 'max-height',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'padding-block', 'padding-inline',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'margin-block', 'margin-inline',
            'top', 'right', 'bottom', 'left', 'inset',
            'border-radius',
            'border-top-left-radius', 'border-top-right-radius',
            'border-bottom-left-radius', 'border-bottom-right-radius',
            'gap', 'row-gap', 'column-gap',
        ];
        $length_props = [
            'border-width',
            'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
        ];
        $length_number_keyword_props = [
            'font-size', 'line-height', 'letter-spacing', 'word-spacing',
        ];
        $number_props = [
            'opacity', 'flex-grow', 'flex-shrink', 'z-index', 'order',
        ];
        $keyword_props = [
            'display', 'position',
            'flex-direction', 'flex-wrap',
            'justify-content', 'align-items', 'align-content', 'align-self',
            'text-align', 'text-transform', 'text-decoration',
            'font-weight', 'font-style',
            'border-style',
            'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style',
            'cursor', 'visibility',
            'overflow', 'overflow-x', 'overflow-y',
            'white-space', 'word-break', 'box-sizing',
        ];
        $string_keyword_props = ['font-family'];
        $string_props         = ['content'];
        $url_keyword_props    = ['background-image', 'list-style-image'];
        $any_props = [
            'background', 'border', 'outline', 'box-shadow', 'text-shadow',
            'transform', 'transition', 'animation',
            'grid-template-columns', 'grid-template-rows', 'grid-template-areas', 'grid-area',
            'font',
        ];

        $map = [];
        foreach ($color_props as $p) {
            $map[$p] = ['color'];
        }
        foreach ($length_keyword_props as $p) {
            $map[$p] = ['length', 'keyword'];
        }
        foreach ($length_props as $p) {
            $map[$p] = ['length'];
        }
        foreach ($length_number_keyword_props as $p) {
            $map[$p] = ['length', 'number', 'keyword'];
        }
        foreach ($number_props as $p) {
            $map[$p] = ['number'];
        }
        foreach ($keyword_props as $p) {
            $map[$p] = ['keyword'];
        }
        foreach ($string_keyword_props as $p) {
            $map[$p] = ['string', 'keyword'];
        }
        foreach ($string_props as $p) {
            $map[$p] = ['string'];
        }
        foreach ($url_keyword_props as $p) {
            $map[$p] = ['url', 'keyword'];
        }
        foreach ($any_props as $p) {
            $map[$p] = ['any'];
        }
        return apply_filters('dynamo_binding_properties', $map);
    }

    private static function requirement_map(): array {
        $grid_container_props = [
            'grid-template-columns', 'grid-template-rows', 'grid-template-areas',
            'grid-auto-flow', 'grid-auto-columns', 'grid-auto-rows',
            'gap', 'row-gap', 'column-gap',
        ];
        $flex_container_props = [
            'flex-direction', 'flex-wrap',
            'justify-content', 'align-items', 'align-content',
        ];
        $positioned_props = [
            'top', 'right', 'bottom', 'left', 'inset', 'z-index',
        ];

        $map = [];
        foreach ($grid_container_props as $p) {
            $map[$p] = ['display' => 'grid'];
        }
        foreach ($flex_container_props as $p) {
            $map[$p] = ['display' => 'flex'];
        }
        foreach ($positioned_props as $p) {
            $map[$p] = ['position' => 'relative'];
        }
        return apply_filters('dynamo_binding_requirements', $map);
    }

    private static function parent_requirement_list(): array {
        return apply_filters('dynamo_binding_parent_requirements', [
            'grid-column', 'grid-row', 'grid-area',
            'align-self', 'justify-self',
            'flex-grow', 'flex-shrink', 'order',
        ]);
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
