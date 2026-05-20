<?php
declare(strict_types=1);

/**
 * Consent Placeholder template.
 *
 * Variables expected:
 *   string $service_name     — e.g. "YouTube"
 *   string $consent_category — e.g. "marketing"
 *
 * Override by placing this file at the same path in a child theme.
 */
?>
<div class="dynamo-consent-placeholder"
     style="background-color: var(--cookie-background, #f9f9f9); color: var(--cookie-text, #111827); border: 2px solid var(--cookie-primary, #3b82f6); padding: 2rem; text-align: center; border-radius: 4px;"
     data-service="<?php echo esc_attr( $service_name ); ?>"
     data-category="<?php echo esc_attr( $consent_category ); ?>"
     data-embed="<?php echo esc_attr( $embed_html ?? '' ); ?>">
    <span class="screen-reader-text"><?php echo esc_html( sprintf( 'This %s embed requires %s consent.', $service_name, $consent_category ) ); ?></span>
    <p><strong><?php echo esc_html( $service_name ); ?></strong></p>
    <p><?php echo esc_html( sprintf( 'This content requires %s cookies to display.', $consent_category ) ); ?></p>
</div>
