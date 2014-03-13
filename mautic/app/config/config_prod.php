<?php

$loader->import("config.php");

/*
$container->loadFromExtension("framework", array(
    "validation" => array(
        "cache" => "apc"
    )
));

$container->loadFromExtension("doctrine", array(
    "orm" => array(
        "metadata_cache_driver" => "apc",
        "result_cache_driver"   => "apc",
        "query_cache_driver"    => "apc"
    )
));
*/

$container->loadFromExtension("monolog", array(
    "handlers" => array(
        "main"    => array(
            "type"         => "fingers_crossed",
            "action_level" => "error",
            "handler"      => "nested"
        ),
        "nested"  => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/%kernel.environment%.log",
            "level" => "debug"
        ),
        "console" => array(
            "type" => "console"
        )
    )
));