<?php
$loader->import("config.php");

if (file_exists(__DIR__ . '/security_local.php')) {
    $loader->import("security_local.php");
} else {
    $loader->import("security.php");
}

//Twig Configuration
$container->loadFromExtension('twig', array(
    'cache'                => false,
    'debug'                => '%kernel.debug%',
    'strict_variables'     => '%kernel.debug%'
));

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
    'channels' => array(
        'mautic',
    ),
    "handlers" => array(
        "main"    => array(
            "formatter" => "mautic.monolog.fulltrace.formatter",
            "type"      => "rotating_file",
            "path"      => "%kernel.logs_dir%/%kernel.environment%.php",
            "level"     => "debug",
            "channels"  => array(
                "!mautic"
            ),
            "max_files" => 7
        ),
        "console" => array(
            "type"   => "console",
            "bubble" => false
        ),
        "mautic"    => array(
            "formatter" => "mautic.monolog.fulltrace.formatter",
            "type"      => "rotating_file",
            "path"      => "%kernel.logs_dir%/mautic_%kernel.environment%.php",
            "level"     => "debug",
            'channels'  => array(
                'mautic',
            ),
            "max_files" => 7
        )
    )
));

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__ . '/config_override.php')) {
    $loader->import("config_override.php");
}

// Allow local settings without committing to git such as swift mailer delivery address overrides
if (file_exists(__DIR__ . '/config_local.php')) {
    $loader->import("config_local.php");
}