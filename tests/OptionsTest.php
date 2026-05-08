<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter']              = [];
        $GLOBALS['wp_transients']          = [];
        $GLOBALS['wp_theme_pages']         = [];
        $GLOBALS['wp_registered_settings'] = [];
        $GLOBALS['wp_enqueued_scripts']      = [];
        $GLOBALS['wp_enqueued_script_deps']  = [];
        $GLOBALS['wp_options']               = [];
    }

    private function makeOptions(): Dynamo_Options {
        return new Dynamo_Options();
    }

    public function test_init_registers_admin_menu_hook(): void {
        $this->makeOptions()->init();
        $this->assertArrayHasKey('admin_menu', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_admin_init_hook(): void {
        $this->makeOptions()->init();
        $this->assertArrayHasKey('admin_init', $GLOBALS['wp_filter']);
    }

    public function test_init_registers_admin_enqueue_scripts_hook(): void {
        $this->makeOptions()->init();
        $this->assertArrayHasKey('admin_enqueue_scripts', $GLOBALS['wp_filter']);
    }

    public function test_register_menu_adds_theme_page(): void {
        $this->makeOptions()->register_menu();
        $this->assertContains('dynamo-options', $GLOBALS['wp_theme_pages']);
    }

    public function test_register_setting_group_calls_register_setting(): void {
        $this->makeOptions()->register_setting_group();
        $names = array_column($GLOBALS['wp_registered_settings'], 'name');
        $this->assertContains('dynamo_options', $names);
    }

    public function test_enqueue_scripts_enqueues_on_correct_hook(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $this->assertContains('dynamo-admin', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_scripts_does_not_enqueue_on_other_pages(): void {
        $this->makeOptions()->enqueue_scripts('edit.php');
        $this->assertNotContains('dynamo-admin', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_render_page_outputs_root_div(): void {
        ob_start();
        $this->makeOptions()->render_page();
        $output = ob_get_clean();
        $this->assertStringContainsString('<div id="dynamo-options-root">', $output);
    }

    public function test_sanitize_returns_array(): void {
        $result = $this->makeOptions()->sanitize(['layout_mode' => 'boxed']);
        $this->assertIsArray($result);
        $this->assertSame('boxed', $result['layout_mode']);
    }

    public function test_sanitize_casts_non_array_to_array(): void {
        $result = $this->makeOptions()->sanitize('bad');
        $this->assertIsArray($result);
    }

    public function test_init_registers_body_class_filter(): void {
        $this->makeOptions()->init();
        $this->assertArrayHasKey('body_class', $GLOBALS['wp_filter']);
    }

    public function test_add_layout_body_class_appends_mode_class(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['layout_mode' => 'boxed'];
        $classes = $this->makeOptions()->add_layout_body_class([]);
        $this->assertContains('dynamo-layout-boxed', $classes);
    }

    public function test_add_layout_body_class_defaults_to_full_width(): void {
        $classes = $this->makeOptions()->add_layout_body_class([]);
        $this->assertContains('dynamo-layout-full-width', $classes);
    }

    public function test_add_layout_body_class_preserves_existing_classes(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['layout_mode' => 'sidebar-left'];
        $classes = $this->makeOptions()->add_layout_body_class(['existing-class']);
        $this->assertContains('existing-class', $classes);
        $this->assertContains('dynamo-layout-sidebar-left', $classes);
    }

    public function test_is_feature_enabled_returns_true_by_default(): void {
        $this->assertTrue(Dynamo_Options::is_feature_enabled('sticky_header'));
    }

    public function test_is_feature_enabled_returns_false_when_disabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['features' => ['sticky_header' => false]];
        $this->assertFalse(Dynamo_Options::is_feature_enabled('sticky_header'));
    }

    public function test_is_feature_enabled_returns_true_when_explicitly_enabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['features' => ['breadcrumbs' => true]];
        $this->assertTrue(Dynamo_Options::is_feature_enabled('breadcrumbs'));
    }

    public function test_sanitize_rejects_invalid_layout_mode(): void {
        $result = $this->makeOptions()->sanitize(['layout_mode' => 'evil-mode']);
        $this->assertSame('full-width', $result['layout_mode']);
    }

    public function test_sanitize_keeps_valid_layout_mode(): void {
        $result = $this->makeOptions()->sanitize(['layout_mode' => 'sidebar-right']);
        $this->assertSame('sidebar-right', $result['layout_mode']);
    }

    public function test_sanitize_casts_features_to_booleans(): void {
        $result = $this->makeOptions()->sanitize(['features' => ['sticky_header' => '1', 'breadcrumbs' => '0']]);
        $this->assertTrue($result['features']['sticky_header']);
        $this->assertFalse($result['features']['breadcrumbs']);
    }

    public function test_sanitize_defaults_layout_mode_to_full_width_when_missing(): void {
        $result = $this->makeOptions()->sanitize([]);
        $this->assertSame('full-width', $result['layout_mode']);
    }

    public function test_sanitize_preserves_performance_settings(): void {
        $result = $this->makeOptions()->sanitize([
            'performance' => ['disable_google_fonts' => '1', 'disable_emoji' => '0', 'remove_jquery_migrate' => '1'],
        ]);
        $this->assertTrue($result['performance']['disable_google_fonts']);
        $this->assertFalse($result['performance']['disable_emoji']);
        $this->assertTrue($result['performance']['remove_jquery_migrate']);
    }

    public function test_sanitize_performance_defaults_to_empty_when_missing(): void {
        $result = $this->makeOptions()->sanitize([]);
        $this->assertSame([], $result['performance']);
    }

    public function test_is_performance_enabled_returns_false_by_default(): void {
        $this->assertFalse(Dynamo_Options::is_performance_enabled('disable_google_fonts'));
    }

    public function test_is_performance_enabled_returns_true_when_set(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['performance' => ['disable_emoji' => true]];
        $this->assertTrue(Dynamo_Options::is_performance_enabled('disable_emoji'));
    }

    public function test_init_registers_init_hook_for_performance(): void {
        $this->makeOptions()->init();
        $this->assertArrayHasKey('init', $GLOBALS['wp_filter']);
    }

    public function test_google_fonts_filter_registered_when_setting_enabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['performance' => ['disable_google_fonts' => true]];
        $this->makeOptions()->apply_performance_settings();
        $this->assertArrayHasKey('style_loader_src', $GLOBALS['wp_filter']);
    }

    public function test_emoji_hooks_removed_when_disabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['performance' => ['disable_emoji' => true]];
        $GLOBALS['wp_removed_actions'] = [];
        $this->makeOptions()->apply_performance_settings();
        $this->assertContains('wp_head', $GLOBALS['wp_removed_actions']);
    }

    public function test_jquery_migrate_dequeued_when_setting_enabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['performance' => ['remove_jquery_migrate' => true]];
        $GLOBALS['wp_dequeued_scripts'] = [];
        $this->makeOptions()->apply_performance_settings();
        $this->assertContains('jquery-migrate', $GLOBALS['wp_dequeued_scripts']);
    }

    // --- Issue #16: vanilla JS enqueue ---

    public function test_enqueue_scripts_registers_admin_hooks_handle(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $this->assertContains('dynamo-admin-hooks', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_scripts_registers_admin_tab_builder_handle(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $this->assertContains('dynamo-admin-tab-builder', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_scripts_registers_admin_state_handle(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $this->assertContains('dynamo-admin-state', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_scripts_registers_admin_ui_handle(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $this->assertContains('dynamo-admin-ui', $GLOBALS['wp_enqueued_scripts']);
    }

    public function test_enqueue_scripts_does_not_use_react_dependencies(): void {
        $this->makeOptions()->enqueue_scripts('appearance_page_dynamo-options');
        $all_deps = array_merge(...array_values($GLOBALS['wp_enqueued_script_deps']));
        $this->assertNotContains('wp-element', $all_deps);
        $this->assertNotContains('wp-components', $all_deps);
        $this->assertNotContains('wp-i18n', $all_deps);
    }

    public function test_enqueue_scripts_skips_all_handles_on_other_pages(): void {
        $this->makeOptions()->enqueue_scripts('edit.php');
        $this->assertEmpty($GLOBALS['wp_enqueued_scripts']);
    }
}
