<?php
declare(strict_types=1);

class FakeCustomizeManager {
    public array $panels   = [];
    public array $sections = [];
    public array $settings = [];
    public array $controls = [];

    public function add_panel(string $id, array $args): void {
        $this->panels[$id] = $args;
    }

    public function add_section(string $id, array $args): void {
        $this->sections[$id] = $args;
    }

    public function add_setting(string $id, array $args): void {
        $this->settings[$id] = $args;
    }

    public function add_control(object $control): void {
        $this->controls[] = $control;
    }
}
