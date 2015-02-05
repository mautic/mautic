<?php
$loader->import("config.php");

if (file_exists(__DIR__ . '/security_local.php')) {
    $loader->import("security_local.php");
} else {
    $loader->import("security.php");
}

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
    "channels" => array(
        "mautic",
    ),
    "handlers" => array(
        "main"    => array(
            "type"         => "fingers_crossed",
            "buffer_size"  => "200",
            "action_level" => "error",
            "handler"      => "nested",
            "channels" => array(
                "!mautic"
            ),
        ),
        "nested"  => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/%kernel.environment%.php",
            "level" => "error"
        ),
        "console" => array(
            "type" => "console"
        ),
        "mautic"    => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/mautic_%kernel.environment%.php",
            "level" => "error",
            'channels' => array(
                'mautic',
            ),
        )
    )
));