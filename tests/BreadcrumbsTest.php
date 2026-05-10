<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BreadcrumbsTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_options']         = [];
        $GLOBALS['wp_is_front_page']   = false;
    }

    public function test_render_outputs_nothing_when_feature_disabled(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['features' => ['breadcrumbs' => false]];
        ob_start();
        Dynamo_Breadcrumbs::render();
        $this->assertSame('', ob_get_clean());
    }

    public function test_render_outputs_nothing_on_front_page(): void {
        $GLOBALS['wp_options']['dynamo_options'] = ['features' => ['breadcrumbs' => true]];
        $GLOBALS['wp_is_front_page'] = true;
        ob_start();
        Dynamo_Breadcrumbs::render();
        $this->assertSame('', ob_get_clean());
    }
}
