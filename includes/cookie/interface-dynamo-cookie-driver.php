<?php
declare(strict_types=1);

interface Dynamo_Cookie_Driver {

    public function register_palette_sync_hooks(): void;

    public function register_embed_hooks(): void;

    /** @return string[] */
    public function get_consent_categories(): array;
}
