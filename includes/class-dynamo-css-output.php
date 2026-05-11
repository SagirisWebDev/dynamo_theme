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
        $debug = defined('WP_DEBUG') && WP_DEBUG;
        $css   = $debug ? null : $this->cache->get();

        if (null === $css) {
            $css  = $this->generator->generate() ?: '';
            // $file_contents = file_get_contents(get_template_directory() . '/assets/css/style.css') ?: '';
            // $css .= $file_contents;
            if (!$debug) {
                $this->cache->set($css);
            }
        }

        echo '<style id="dynamo-dynamic-css">' . $css . "</style>\n";
    }
}
