<?php
declare(strict_types=1);

class Dynamo_Font_Manifest {

    private string $path;
    private ?array $entries = null;

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

    private function load(): array {
        $raw = @file_get_contents($this->path);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return $decoded;
    }
}
