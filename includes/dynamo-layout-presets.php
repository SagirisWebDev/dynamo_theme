<?php
declare(strict_types=1);

function dynamo_layout_width_presets(): array {
    return apply_filters('dynamo_layout_width_presets', [
        'narrow'    => ['label' => 'Narrow',    'default' => '640px'],
        'default'   => ['label' => 'Default',   'default' => '720px'],
        'wide'      => ['label' => 'Wide',      'default' => '1024px'],
        'container' => ['label' => 'Container', 'default' => '1200px'],
        'full'      => ['label' => 'Full',      'default' => '100%'],
    ]);
}

/**
 * Apply dynamoWidth block attribute as an inline max-width style when core/group renders.
 *
 * core/group is a dynamic (server-rendered) block; WordPress ignores the saved HTML
 * and regenerates the wrapper via PHP. This filter reads the dynamoWidth attribute
 * from the block's comment delimiters and injects the correct CSS variable.
 */
add_filter('render_block_core/group', function (string $block_content, array $block): string {
    $preset = $block['attrs']['dynamoWidth'] ?? '';
    if ('' === $preset) {
        return $block_content;
    }

    $allowed = array_keys(dynamo_layout_width_presets());
    if (!in_array($preset, $allowed, true)) {
        return $block_content;
    }

    $style_value = 'max-width:var(--dynamo-layout-width-' . esc_attr($preset) . ')';

    $processor = new WP_HTML_Tag_Processor($block_content);
    if ($processor->next_tag(['class_name' => 'wp-block-group'])) {
        $existing = $processor->get_attribute('style') ?? '';
        $new_style = '' !== $existing
            ? rtrim($existing, ';') . ';' . $style_value
            : $style_value;
        $processor->set_attribute('style', $new_style);
    }

    return (string) $processor;
}, 10, 2);
