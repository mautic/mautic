<?php

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Mautic\CoreBundle\Test\EnvLoader;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Mock\Client;
use Symfony\Component\DependencyInjection\Reference;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$loader->import('config.php');

EnvLoader::load();

// Define some constants from .env
defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');
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
        'enabled' => true,
    ],
    'csrf_protection' => [
        'enabled' => true,
    ],
]);

$container->setParameter('mautic.famework.csrf_protection', true);

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
                'charset'  => 'utf8mb4',
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
            'level'     => getenv('MAUTIC_DEBUG_LEVEL') ?: 'error',
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
            'level'     => getenv('MAUTIC_DEBUG_LEVEL') ?: 'error',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
    ],
]);

$container->loadFromExtension('liip_test_fixtures', [
    'cache_db' => [
        'sqlite' => 'liip_functional_test.services_database_backup.sqlite',
    ],
    'keep_database_and_schema' => true,
]);

$loader->import('security_test.php');

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__.'/config_override.php')) {
    $loader->import('config_override.php');
}

// Add required parameters
$container->setParameter('mautic.secret_key', '68c7e75470c02cba06dd543431411e0de94e04fdf2b3a2eac05957060edb66d0');
$container->setParameter('mautic.security.disableUpdates', true);
$container->setParameter('mautic.rss_notification_url', null);
$container->setParameter('mautic.batch_sleep_time', 0);

// Turn off creating of indexes in lead field fixtures
$container->register('mautic.install.fixture.lead_field', \Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class)
    ->addArgument(false)
    ->addTag(FixturesCompilerPass::FIXTURE_TAG)
    ->setPublic(true);
$container->register('mautic.lead.fixture.contact_field', \Mautic\LeadBundle\DataFixtures\ORM\LoadLeadFieldData::class)
    ->addArgument(false)
    ->addTag(FixturesCompilerPass::FIXTURE_TAG)
    ->setPublic(true);

// Use static namespace for token manager
$container->register('security.csrf.token_manager', \Symfony\Component\Security\Csrf\CsrfTokenManager::class)
    ->addArgument(new Reference('security.csrf.token_generator'))
    ->addArgument(new Reference('security.csrf.token_storage'))
    ->addArgument('test')
    ->setPublic(true);

// HTTP client mock handler providing response queue
$container->register('mautic.http.client.mock_handler', \GuzzleHttp\Handler\MockHandler::class)
    ->setClass('\GuzzleHttp\Handler\MockHandler');

// Stub Guzzle HTTP client to prevent accidental request to third parties
$container->register('mautic.http.client', \GuzzleHttp\Client::class)
    ->setPublic(true)
    ->setFactory('\Mautic\CoreBundle\Test\Guzzle\ClientFactory::stub')
    ->addArgument(new Reference('mautic.http.client.mock_handler'));
