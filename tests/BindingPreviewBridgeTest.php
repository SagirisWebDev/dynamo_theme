<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BindingPreviewBridgeTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['wp_filter'] = [];
    }

    private function colorRegistry(): Dynamo_Binding_Registry {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_bg',
            'type'     => 'color',
            'label'    => 'Header background',
            'section'  => 'header_styling',
            'selector' => '.site-header',
            'property' => 'background-color',
        ]);
        return $registry;
    }

    public function test_metadata_is_keyed_by_setting_id(): void {
        $map = (new Dynamo_Binding_Preview_Bridge($this->colorRegistry()))->build_metadata();
        $this->assertArrayHasKey('dynamo_header_bg', $map);
    }

    public function test_metadata_contains_selector_and_property(): void {
        $map = (new Dynamo_Binding_Preview_Bridge($this->colorRegistry()))->build_metadata();
        $entry = $map['dynamo_header_bg'];
        $this->assertSame('.site-header', $entry['selector']);
        $this->assertSame('background-color', $entry['property']);
    }

    public function test_color_binding_metadata_omits_choices_map(): void {
        $map = (new Dynamo_Binding_Preview_Bridge($this->colorRegistry()))->build_metadata();
        $this->assertArrayNotHasKey('choicesMap', $map['dynamo_header_bg']);
    }

    public function test_color_binding_metadata_omits_unit(): void {
        $map = (new Dynamo_Binding_Preview_Bridge($this->colorRegistry()))->build_metadata();
        $this->assertArrayNotHasKey('unit', $map['dynamo_header_bg']);
    }

    public function test_empty_registry_produces_empty_map(): void {
        $map = (new Dynamo_Binding_Preview_Bridge(new Dynamo_Binding_Registry()))->build_metadata();
        $this->assertSame([], $map);
    }

    public function test_range_binding_with_unit_emits_unit_in_metadata(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_pad',
            'type'     => 'range',
            'label'    => 'Header pad',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'padding-block',
            'unit'     => 'rem',
        ]);
        $map = (new Dynamo_Binding_Preview_Bridge($registry))->build_metadata();
        $this->assertSame('rem', $map['dynamo_header_pad']['unit']);
    }

    public function test_number_binding_without_unit_omits_unit(): void {
        $registry = new Dynamo_Binding_Registry();
        $registry->register([
            'id'       => 'header_opacity',
            'type'     => 'number',
            'label'    => 'Header opacity',
            'section'  => 'header',
            'selector' => '.site-header',
            'property' => 'opacity',
        ]);
        $map = (new Dynamo_Binding_Preview_Bridge($registry))->build_metadata();
        $this->assertArrayNotHasKey('unit', $map['dynamo_header_opacity']);
    }
}
