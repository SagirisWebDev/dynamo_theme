<?php
declare(strict_types=1);

trait MakesCustomizer {

    private function make_customizer(?Dynamo_Token_Registry $registry = null): Dynamo_Customizer {
        $registry ??= new Dynamo_Token_Registry();
        $fonts = new Dynamo_Font_Manifest(__DIR__ . '/fixtures/font-manifest/valid.json');
        return new Dynamo_Customizer(
            $registry,
            new Dynamo_CSS_Cache(),
            new Dynamo_CSS_Generator($registry, $fonts),
            $fonts
        );
    }
}
