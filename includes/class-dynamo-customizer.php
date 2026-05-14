<?php
declare(strict_types=1);

class Dynamo_Customizer {

    private Dynamo_Token_Registry  $registry;
    private Dynamo_CSS_Cache       $cache;
    private Dynamo_CSS_Generator   $generator;
    private Dynamo_Font_Manifest   $fonts;

    public function __construct(Dynamo_Token_Registry $registry, Dynamo_CSS_Cache $cache, Dynamo_CSS_Generator $generator, Dynamo_Font_Manifest $fonts) {
        $this->registry  = $registry;
        $this->cache     = $cache;
        $this->generator = $generator;
        $this->fonts     = $fonts;
    }

    public function init(): void {
        add_action('customize_register', [$this, 'register']);
        add_action('customize_preview_init', [$this, 'enqueue_preview_script']);
        add_action('customize_save_after', fn() => dynamo_bust_css_cache());
    }

    public function register(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_colours', [
            'title'    => __('Dynamo: Colours', 'dynamo'),
            'priority' => 30,
        ]);

        $wp_customize->add_section('dynamo_colours_section', [
            'title' => __('Colours', 'dynamo'),
            'panel' => 'dynamo_colours',
        ]);

        $colour_controls = [
            'colors-primary'     => __('Primary Colour', 'dynamo'),
            'colors-secondary'   => __('Secondary Colour', 'dynamo'),
            'colors-accent'      => __('Accent Colour', 'dynamo'),
            'colors-background'  => __('Background', 'dynamo'),
            'colors-text'        => __('Text Colour', 'dynamo'),
            'colors-link'        => __('Link Colour', 'dynamo'),
            'colors-section-alt' => __('Section Alt Background', 'dynamo'),
        ];

        foreach ($colour_controls as $token => $label) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $wp_customize->add_setting($setting_id, [
                'default'           => $this->registry->get($token) ?? '#000000',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'postMessage',
            ]);
            $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $setting_id, [
                'label'   => $label,
                'section' => 'dynamo_colours_section',
            ]));
        }

        $this->register_typography($wp_customize);
        $this->register_spacing($wp_customize);
        $this->register_layout($wp_customize);
        $this->register_borders_and_shadows($wp_customize);
    }

    private function register_typography(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_typography', [
            'title'    => __('Dynamo: Typography', 'dynamo'),
            'priority' => 31,
        ]);

        $elements = [
            'body' => __('Body Text', 'dynamo'),
            'h1'   => __('Heading 1 (H1)', 'dynamo'),
            'h2'   => __('Heading 2 (H2)', 'dynamo'),
            'h3'   => __('Heading 3 (H3)', 'dynamo'),
            'h4'   => __('Heading 4 (H4)', 'dynamo'),
            'h5'   => __('Heading 5 (H5)', 'dynamo'),
            'h6'   => __('Heading 6 (H6)', 'dynamo'),
        ];

        $font_weight_choices = [
            '400' => __('Regular (400)', 'dynamo'),
            '500' => __('Medium (500)', 'dynamo'),
            '600' => __('Semi-bold (600)', 'dynamo'),
            '700' => __('Bold (700)', 'dynamo'),
        ];

        foreach ($elements as $element => $element_label) {
            $section_id = 'dynamo_typography_' . $element;
            $wp_customize->add_section($section_id, [
                'title' => $element_label,
                'panel' => 'dynamo_typography',
            ]);

            $font_family_choices = $this->font_family_choices();
            $fonts               = $this->fonts;
            $sanitize_font_slug  = static function($value) use ($fonts): string {
                $value = is_string($value) ? $value : '';
                return $fonts->has($value) ? $value : 'system-sans';
            };

            $controls = [
                'font-family' => [
                    'label'             => __('Font Family', 'dynamo'),
                    'type'              => 'select',
                    'choices'           => $font_family_choices,
                    'sanitize_callback' => $sanitize_font_slug,
                ],
                'font-size' => [
                    'label' => __('Font Size', 'dynamo'),
                    'type'  => 'text',
                ],
                'font-weight' => [
                    'label'   => __('Font Weight', 'dynamo'),
                    'type'    => 'select',
                    'choices' => $font_weight_choices,
                ],
                'line-height' => [
                    'label' => __('Line Height', 'dynamo'),
                    'type'  => 'text',
                ],
            ];

            foreach ($controls as $prop => $control_args) {
                $token      = "typography-{$element}-{$prop}";
                $setting_id = 'dynamo_' . str_replace('-', '_', $token);

                $wp_customize->add_setting($setting_id, [
                    'default'           => $this->registry->get($token) ?? '',
                    'sanitize_callback' => $control_args['sanitize_callback'] ?? 'sanitize_text_field',
                    'transport'         => 'postMessage',
                ]);

                $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                    'label'   => $control_args['label'],
                    'section' => $section_id,
                    'type'    => $control_args['type'],
                    'choices' => $control_args['choices'] ?? [],
                ]));
            }
        }
    }

    private function register_spacing(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_spacing', [
            'title'    => __('Dynamo: Spacing', 'dynamo'),
            'priority' => 32,
        ]);

        $sections = [
            'header'  => __('Header', 'dynamo'),
            'footer'  => __('Footer', 'dynamo'),
            'content' => __('Content', 'dynamo'),
        ];

        $section_controls = [
            'header'  => ['padding-top', 'padding-bottom'],
            'footer'  => ['padding-top', 'padding-bottom'],
            'content' => ['padding-top', 'padding-bottom', 'padding-x'],
        ];

        $labels = [
            'padding-top'    => __('Padding Top', 'dynamo'),
            'padding-bottom' => __('Padding Bottom', 'dynamo'),
            'padding-x'      => __('Horizontal Padding', 'dynamo'),
        ];

        foreach ($sections as $area => $section_label) {
            $section_id = 'dynamo_spacing_' . $area;
            $wp_customize->add_section($section_id, [
                'title' => $section_label,
                'panel' => 'dynamo_spacing',
            ]);

            foreach ($section_controls[$area] as $prop) {
                $token      = "spacing-{$area}-{$prop}";
                $setting_id = 'dynamo_' . str_replace('-', '_', $token);

                $wp_customize->add_setting($setting_id, [
                    'default'           => $this->registry->get($token) ?? '0',
                    'sanitize_callback' => 'sanitize_text_field',
                    'transport'         => 'postMessage',
                ]);

                $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                    'label'   => $labels[$prop],
                    'section' => $section_id,
                    'type'    => 'text',
                ]));
            }
        }
    }

    private function register_layout(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_layout', [
            'title'    => __('Dynamo: Layout', 'dynamo'),
            'priority' => 33,
        ]);

        $wp_customize->add_section('dynamo_layout_section', [
            'title' => __('Layout', 'dynamo'),
            'panel' => 'dynamo_layout',
        ]);

        $controls = [
            'layout-container-max-width' => __('Container Max Width', 'dynamo'),
            'layout-content-width'       => __('Content Width', 'dynamo'),
            'layout-sidebar-width'       => __('Sidebar Width', 'dynamo'),
        ];

        foreach ($controls as $token => $label) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $wp_customize->add_setting($setting_id, [
                'default'           => $this->registry->get($token) ?? '',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'postMessage',
            ]);
            $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                'label'   => $label,
                'section' => 'dynamo_layout_section',
                'type'    => 'text',
            ]));
        }
    }

    private function register_borders_and_shadows(object $wp_customize): void {
        $wp_customize->add_panel('dynamo_borders_shadows', [
            'title'    => __('Dynamo: Borders & Shadows', 'dynamo'),
            'priority' => 34,
        ]);

        $wp_customize->add_section('dynamo_borders_shadows_section', [
            'title' => __('Borders & Shadows', 'dynamo'),
            'panel' => 'dynamo_borders_shadows',
        ]);

        $controls = [
            'borders-radius' => __('Border Radius', 'dynamo'),
            'borders-color'  => __('Border Colour', 'dynamo'),
            'borders-width'  => __('Border Width', 'dynamo'),
            'shadows-sm'     => __('Shadow Small', 'dynamo'),
            'shadows-md'     => __('Shadow Medium', 'dynamo'),
            'shadows-lg'     => __('Shadow Large', 'dynamo'),
        ];

        foreach ($controls as $token => $label) {
            $setting_id = 'dynamo_' . str_replace('-', '_', $token);
            $wp_customize->add_setting($setting_id, [
                'default'           => $this->registry->get($token) ?? '',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'postMessage',
            ]);
            $wp_customize->add_control(new WP_Customize_Control($wp_customize, $setting_id, [
                'label'   => $label,
                'section' => 'dynamo_borders_shadows_section',
                'type'    => 'text',
            ]));
        }
    }

    private function font_family_choices(): array {
        $choices = [];
        foreach ($this->fonts->all() as $slug => $entry) {
            $choices[$slug] = (string) ($entry['label'] ?? $slug);
        }
        return $choices;
    }

    public function enqueue_preview_script(): void {
        wp_enqueue_script(
            'dynamo-customizer-preview',
            DYNAMO_URL . 'assets/js/customizer-preview.js',
            ['customize-preview'],
            DYNAMO_VERSION,
            true
        );

        
        $css = $this->cache->get() ?? $this->generator->generate() ?? '';                                           
        wp_localize_script('dynamo-customizer-preview', 'dynamoPreview', apply_filters('dynamo_customizer_preview_data', [
            'initialCss' => $css,
        ])); 
    }
}
