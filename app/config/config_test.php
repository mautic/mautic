<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$loader->import('config.php');

$container->loadFromExtension('framework', [
    'test'    => true,
    'session' => [
        'storage_id' => 'session.storage.filesystem',
    ],
    'profiler' => [
        'collect' => false,
    ],
    'translator' => [
        'enabled' => false,
    ],
]);

$container->loadFromExtension('web_profiler', [
    'toolbar'             => false,
    'intercept_redirects' => false,
]);

$container->loadFromExtension('swiftmailer', [
    'disable_delivery' => true,
]);

$container->loadFromExtension('doctrine', [
    'dbal' => [
        'default_connection' => 'default',
        'connections'        => [
            'default' => [
                'driver'   => 'pdo_mysql',
                'host'     => isset($_SERVER['DB_HOST']) ? $_SERVER['DB_HOST'] : '%mautic.db_host%',
                'port'     => isset($_SERVER['DB_PORT']) ? $_SERVER['DB_PORT'] : '%mautic.db_port%',
                'dbname'   => isset($_SERVER['DB_NAME']) ? $_SERVER['DB_NAME'] : '%mautic.db_name%',
                'user'     => isset($_SERVER['DB_USER']) ? $_SERVER['DB_USER'] : '%mautic.db_user%',
                'password' => isset($_SERVER['DB_PASSWD']) ? $_SERVER['DB_PASSWD'] : '%mautic.db_password%',
                'charset'  => 'UTF8',
                // Prevent Doctrine from crapping out with "unsupported type" errors due to it examining all tables in the database and not just Mautic's
                'mapping_types' => [
                    'enum'  => 'string',
                    'point' => 'string',
                    'bit'   => 'string',
                ],

            ],
        ],
    ],
]);

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
    ],
    'handlers' => [
        'main' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                '!mautic',
            ],
            'max_files' => 7,
        ],
        'console' => [
            'type'   => 'console',
            'bubble' => false,
        ],
        'mautic' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/mautic_%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
    ],
]);

$container->loadFromExtension('liip_functional_test', [
    'cache_sqlite_db' => true,
]);

$loader->import('security_test.php');

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__.'/config_override.php')) {
    $loader->import('config_override.php');
}
