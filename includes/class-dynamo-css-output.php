<?php
declare(strict_types=1);

class Dynamo_CSS_Output {

    private Dynamo_CSS_Generator $generator;
    private Dynamo_CSS_Cache     $cache;

    public function __construct(Dynamo_CSS_Generator $generator, Dynamo_CSS_Cache $cache) {
        $this->generator = $generator;
        $this->cache     = $cache;
    }

    public function init(): void {
        add_action('wp_head', [$this, 'print_styles']);
    }

    public function print_styles(): void {
        $bypass_cache = (defined('WP_DEBUG') && WP_DEBUG) || is_customize_preview();
        $css          = $bypass_cache ? null : $this->cache->get();

        if (null === $css) {
            $css = $this->generator->generate() ?: '';
            if (!$bypass_cache) {
                $this->cache->set($css);
            }
        }

        echo '<style id="dynamo-dynamic-css">' . $css . "</style>\n";

        if (class_exists('Dynamo_Binding_Registry') && class_exists('Dynamo_Binding_CSS_Renderer')) {
            $renderer = new Dynamo_Binding_CSS_Renderer(Dynamo_Binding_Registry::instance());
            foreach ($renderer->extras_blocks() as $id => $block) {
                echo '<style id="dynamo-binding-extras-' . $id . '">' . $block . "</style>\n";
            }
        }
    }
}
