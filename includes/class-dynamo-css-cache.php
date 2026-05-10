<?php
declare(strict_types=1);

class Dynamo_CSS_Cache {

    private function key(): string {
        $mtime = @filemtime(DYNAMO_PATH . 'assets/css/style.css') ?: 0;
        return 'dynamo_css_' . DYNAMO_VERSION . '_' . $mtime;
    }

    public function get(): ?string {
        $cached = get_transient($this->key());
        return false === $cached ? null : (string) $cached;
    }

    public function set(string $css): void {
        set_transient($this->key(), $css, DAY_IN_SECONDS);
    }

    public function bust(): void {
        delete_transient($this->key());
    }
}
