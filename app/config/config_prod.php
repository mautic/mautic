<?php

$loader->import('config.php');

if (file_exists(__DIR__.'/security_local.php')) {
    $loader->import('security_local.php');
} else {
    $loader->import('security.php');
}

if (function_exists('apcu_store')) {
    // Validation caching does not work in Mautic currently - https://github.com/mautic/mautic/issues/6259
    // $container->loadFromExtension('framework', array(
    //     'validation' => array(
    //         'cache' => 'apcu',
    //     )
    // ));
    $container->loadFromExtension('doctrine', [
        'orm' => [
            'metadata_cache_driver' => 'apcu',
            'query_cache_driver'    => 'apcu',
            // You can use APCu for result caching if using a single node in production, otherwise Redis works well.
            'result_cache_driver'   => 'apcu',
        ],
    ]);
} elseif (function_exists('apc_store')) {
    // Validation caching does not work in Mautic currently - https://github.com/mautic/mautic/issues/6259
    // $container->loadFromExtension('framework', array(
    //     'validation' => array(
    //         'cache' => 'apc',
    //     )
    // ));
    $container->loadFromExtension('doctrine', [
        'orm' => [
            'metadata_cache_driver' => 'apc',
            'query_cache_driver'    => 'apc',
            // You can use APC for result caching if using a single node in production, otherwise Redis works well.
            'result_cache_driver'   => 'apc',
        ],
    ]);
}

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
    ],
    'handlers' => [
        'main' => [
            'type'         => 'fingers_crossed',
            'buffer_size'  => '200',
            'action_level' => 'error',
            'handler'      => 'nested',
            'channels'     => [
                '!mautic',
            ],
        ],
        'nested' => [
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => 'error',
            'max_files' => 7,
        ],
        'mautic' => [
            'type'      => 'service',
            'id'        => 'mautic.monolog.handler',
            'channels'  => [
                'mautic',
            ],
        ],
    ],
]);

//Twig Configuration
$container->loadFromExtension('twig', [
    'cache'       => '%env(resolve:MAUTIC_TWIG_CACHE_DIR)%',
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
