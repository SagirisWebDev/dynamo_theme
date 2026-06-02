<?php
declare(strict_types=1);

function dynamo_border_radius_presets(): array {
    return apply_filters('dynamo_border_radius_presets', [
        'none'    => ['label' => 'None',    'default' => '0'],
        'sm'      => ['label' => 'Small',   'default' => '0.25rem'],
        'default' => ['label' => 'Default', 'default' => '0.375rem'],
        'lg'      => ['label' => 'Large',   'default' => '0.5rem'],
        'xl'      => ['label' => 'X-Large', 'default' => '0.75rem'],
        'pill'    => ['label' => 'Pill',    'default' => '9999px'],
    ]);
}

/**
 * Apply dynamoRadius block attribute as an inline border-radius style when a
 * dynamic (server-rendered) block renders on the frontend.
 *
 * Static blocks have the style written by the JS getSaveContent.extraProps filter.
 * Dynamic blocks (e.g. core/cover) regenerate their wrapper in PHP, discarding
 * the JS-saved HTML. This filter re-applies the style after PHP rendering so the
 * attribute round-trips correctly to the frontend.
 */
add_filter('render_block', function (string $block_content, array $block): string {
    $preset = $block['attrs']['dynamoRadius'] ?? '';
    if ('' === $preset) {
        return $block_content;
    }

    $allowed = array_keys(dynamo_border_radius_presets());
    if (!in_array($preset, $allowed, true)) {
        return $block_content;
    }

    // Skip if the style was already injected by the JS save filter (static blocks).
    if (str_contains($block_content, '--dynamo-borders-radius-')) {
        return $block_content;
    }

    $style_value = 'border-radius:var(--dynamo-borders-radius-' . esc_attr($preset) . ')';

    $processor = new WP_HTML_Tag_Processor($block_content);
    if ($processor->next_tag()) {
        $existing  = $processor->get_attribute('style') ?? '';
        $new_style = '' !== $existing
            ? rtrim($existing, ';') . ';' . $style_value
            : $style_value;
        $processor->set_attribute('style', $new_style);
    }

    return (string) $processor;
}, 10, 2);
