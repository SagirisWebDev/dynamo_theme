<?php
declare(strict_types=1);

class Dynamo_Font_Manifest {

    private const SAFETY_FALLBACK = [
        'system-sans' => [
            'label'    => 'System Sans',
            'fallback' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'faces'    => [],
        ],
    ];

    private string $path;
    private ?array $entries = null;
    private array $errors   = [];
    private bool $is_valid  = true;

    public function __construct(string $path) {
        $this->path = $path;
    }

    public function all(): array {
        if ($this->entries === null) {
            $this->entries = $this->load();
        }
        return $this->entries;
    }

    public function get(string $slug): ?array {
        return $this->all()[$slug] ?? null;
    }

    public function has(string $slug): bool {
        return isset($this->all()[$slug]);
    }

    public function is_valid(): bool {
        $this->all();
        return $this->is_valid;
    }

    public function get_errors(): array {
        $this->all();
        return $this->errors;
    }

    public function render_admin_notice(): void {
        if ($this->is_valid()) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        $heading = esc_html__('Dynamo: font manifest errors', 'dynamo');
        $items   = '';
        foreach ($this->get_errors() as $error) {
            $items .= '<li>' . esc_html($error) . '</li>';
        }
        echo '<div class="notice notice-error is-dismissible"><p><strong>' . $heading . '</strong></p><ul>' . wp_kses_post($items) . '</ul></div>';
    }

    private function load(): array {
        if (!is_readable($this->path)) {
            $this->is_valid = false;
            $this->errors[] = sprintf('Font manifest file not found at %s.', $this->path);
            return self::SAFETY_FALLBACK;
        }
        $raw = @file_get_contents($this->path);
        if ($raw === false) {
            $this->is_valid = false;
            $this->errors[] = sprintf('Font manifest file at %s could not be read.', $this->path);
            return self::SAFETY_FALLBACK;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->is_valid = false;
            $this->errors[] = sprintf('Font manifest at %s contains malformed JSON.', $this->path);
            return self::SAFETY_FALLBACK;
        }
        return $this->validate($decoded);
    }

    private function validate(array $decoded): array {
        $entries = [];
        foreach ($decoded as $slug => $entry) {
            if (!is_string($slug) || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
                $this->is_valid = false;
                $this->errors[] = sprintf('Font manifest entry "%s" has an invalid slug (must match [a-z0-9-]+).', (string) $slug);
                continue;
            }
            if (!is_array($entry) || !isset($entry['label']) || !is_string($entry['label']) || $entry['label'] === '') {
                $this->is_valid = false;
                $this->errors[] = sprintf('Font manifest entry "%s" is missing a required "label" string.', $slug);
                continue;
            }
            if (!isset($entry['fallback']) || !is_string($entry['fallback']) || $entry['fallback'] === '') {
                $this->is_valid = false;
                $this->errors[] = sprintf('Font manifest entry "%s" is missing a required "fallback" string.', $slug);
                continue;
            }
            $entries[$slug] = $entry;
        }
        return $entries;
    }
}
