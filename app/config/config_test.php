<?php

use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Mock\Client;
use Symfony\Component\Dotenv\Dotenv;

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$loader->import('config.php');

// Load environment variables from .env.test file
$env     = new Dotenv();
$root    = __DIR__.'/../../';
$envFile = file_exists($root.'.env') ? $root.'.env' : $root.'.env.dist';

$env->load($envFile);

// Define some constants from .env
defined('MAUTIC_DB_PREFIX') || define('MAUTIC_DB_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');
defined('MAUTIC_ENV') || define('MAUTIC_ENV', getenv('MAUTIC_ENV') ?: 'test');

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
    'csrf_protection' => [
        'enabled' => false,
    ],
]);

$container->setParameter('mautic.famework.csrf_protection', false);

$container->register('mautic_integration.pipedrive.guzzle.client', Client::class);

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
                'host'     => getenv('DB_HOST') ?: '%mautic.db_host%',
                'port'     => getenv('DB_PORT') ?: '%mautic.db_port%',
                'dbname'   => getenv('DB_NAME') ?: '%mautic.db_name%',
                'user'     => getenv('DB_USER') ?: '%mautic.db_user%',
                'password' => getenv('DB_PASSWD') ?: '%mautic.db_password%',
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

// Ensure the mautic.db_table_prefix is set to our phpunit configuration.
$container->setParameter('mautic.db_table_prefix', MAUTIC_TABLE_PREFIX);

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

//Add required parameters
$container->setParameter('mautic.secret_key', '68c7e75470c02cba06dd543431411e0de94e04fdf2b3a2eac05957060edb66d0');
