<?php

$loader->import('config.php');

if (file_exists(__DIR__.'/security_local.php')) {
    $loader->import('security_local.php');
} else {
    $loader->import('security.php');
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

$debugMode = $container->getParameter('kernel.debug');

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
    ],
    'handlers' => [
        'main' => [
            'formatter'    => $debugMode ? 'mautic.monolog.fulltrace.formatter' : '%env(MAUTIC_LOG_MAIN_FORMATTER)%',
            'type'         => 'fingers_crossed',
            'buffer_size'  => '200',
            'action_level' => $debugMode ? 'debug' : '%env(MAUTIC_LOG_MAIN_ACTION_LEVEL)%',
            'handler'      => 'nested',
            'channels'     => [
                '!mautic',
            ],
        ],
        'nested' => [
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => $debugMode ? 'debug' : '%env(MAUTIC_LOG_NESTED_ACTION_LEVEL)%',
            'max_files' => 7,
        ],
        'mautic' => [
            'formatter' => $debugMode ? 'mautic.monolog.fulltrace.formatter' : '%env(MAUTIC_LOG_MAUTIC_FORMATTER)%',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/mautic_%kernel.environment%.php',
            'level'     => $debugMode ? 'debug' : '%env(MAUTIC_LOG_MAUTIC_ACTION_LEVEL)%',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
    ],
]);

//Twig Configuration
$container->loadFromExtension('twig', [
    'cache'       => '%env(MAUTIC_TWIG_CACHE_DIR)%',
    'auto_reload' => true,
    'paths'       => [
        '%kernel.root_dir%/bundles' => 'bundles',
    ],
]);

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__.'/config_override.php')) {
    $loader->import('config_override.php');
}

// Allow local settings without committing to git such as swift mailer delivery address overrides
if (file_exists(__DIR__.'/config_local.php')) {
    $loader->import('config_local.php');
}
