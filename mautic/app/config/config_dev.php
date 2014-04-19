<?php
$loader->import("config.php");
$loader->import("security.php");

$container->loadFromExtension('framework', array(
    "router"   => array(
        "resource"            => "%kernel.root_dir%/config/routing_dev.php",
        "strict_requirements" => true
    ),
    "profiler" => array(
        "only_exceptions" => false
    )
));

$container->loadFromExtension("web_profiler", array(
    "toolbar"             => true,
    "intercept_redirects" => false
));

$container->loadFromExtension("monolog", array(
    "handlers" => array(
        "main"    => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/%kernel.environment%.log",
            "level" => "debug"
        ),
        "console" => array(
            "type"   => "console",
            "bubble" => false
        ),
        // uncomment to get logging in your browser
        // you may have to allow bigger header sizes in your Web server configuration
        /*
        "firephp" => array(
            "type"  => "firephp",
            "level" => "info"
        ),
        "chromephp" => array(
            "type"  => "chromephp",
            "level" => "info"
        ),
        */
    )
));

/*
$container->loadFromExtension("swiftmailer", array(
    "delivery_address" => "me@example.com"
));
*/

if ($container->getParameter('mautic.api_enabled')) {
    //Load API doc
    $container->loadFromExtension('nelmio_api_doc', array());
}