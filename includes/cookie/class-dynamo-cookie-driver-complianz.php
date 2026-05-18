<?php
declare(strict_types=1);

class Dynamo_Cookie_Driver_Complianz implements Dynamo_Cookie_Driver {

    public function register_palette_sync_hooks(): void {
        add_action('cmplz_after_consent', static fn() => null);
    }

    public function register_embed_hooks(): void {
        // Stub — wired in a subsequent issue.
    }

    public function get_consent_categories(): array {
        return ['statistics', 'marketing', 'preferences'];
    }
}
