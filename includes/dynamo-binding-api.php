<?php
declare(strict_types=1);

if (!function_exists('dynamo_config_customizer')) {
    function dynamo_config_customizer(array $args): void {
        Dynamo_Binding_Registry::instance()->register($args);
    }
}
