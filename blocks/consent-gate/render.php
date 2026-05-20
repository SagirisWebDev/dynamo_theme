<?php
declare(strict_types=1);

if (function_exists('wp_enqueue_script') && defined('DYNAMO_URL') && defined('DYNAMO_VERSION')) {
    wp_enqueue_script(
        'dynamo-consent-gate-frontend',
        DYNAMO_URL . 'blocks/consent-gate/frontend.js',
        [],
        DYNAMO_VERSION,
        true
    );
}

$category = esc_attr( $attributes['consentCategory'] ?? '' );
?>
<div class="dynamo-consent-gate" style="display:none" data-consent-category="<?php echo $category; ?>">
    <?php echo $content; ?>
</div>
