<?php

use Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Mautic\CoreBundle\Test\EnvLoader;
use Symfony\Component\DependencyInjection\Reference;

/** @var Symfony\Component\DependencyInjection\ContainerBuilder $container */

// Include path settings
$root          = $container->getParameter('mautic.application_dir').'/app';
$configBaseDir = ParameterLoader::getLocalConfigBaseDir($root);

$loader->import('config.php');

EnvLoader::load();

// Define some constants from .env
defined('MAUTIC_TABLE_PREFIX') || define('MAUTIC_TABLE_PREFIX', getenv('MAUTIC_DB_PREFIX') ?: '');
defined('MAUTIC_ENV') || define('MAUTIC_ENV', getenv('MAUTIC_ENV') ?: 'test');

// Twig Configuration
$container->loadFromExtension('twig', [
    'cache'            => false,
    'debug'            => '%kernel.debug%',
    'strict_variables' => true,
    'paths'            => [
        '%mautic.application_dir%/app/bundles'                  => 'bundles',
        '%mautic.application_dir%/app/bundles/CoreBundle'       => 'MauticCore',
        '%mautic.application_dir%/themes'                       => 'themes',
    ],
    'form_themes' => [
        // Can be found at bundles/CoreBundle/Resources/views/mautic_form_layout.html.twig
        '@MauticCore/FormTheme/mautic_form_layout.html.twig',
    ],
]);

$container->loadFromExtension('framework', [
    'test'    => true,
    'session' => [
        'storage_factory_id' => 'session.storage.factory.mock_file',
        'name'               => 'MOCKSESSION',
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

$connectionSettings = [
    'host'     => '%env(DB_HOST)%' ?: '%mautic.db_host%',
    'port'     => '%env(DB_PORT)%' ?: '%mautic.db_port%',
    'dbname'   => '%env(DB_NAME)%' ?: '%mautic.db_name%',
    'user'     => '%env(DB_USER)%' ?: '%mautic.db_user%',
    'password' => '%env(DB_PASSWD)%' ?: '%mautic.db_password%',
    'options'  => [PDO::ATTR_STRINGIFY_FETCHES => true], // @see https://www.php.net/manual/en/migration81.incompatible.php#migration81.incompatible.pdo.mysql
];
$container->loadFromExtension('doctrine', [
    'dbal' => [
        'connections' => [
            'default'    => $connectionSettings,
            'unbuffered' => $connectionSettings,
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
if (file_exists($configBaseDir.'/config/config_override.php')) {
    $loader->import($configBaseDir.'/config/config_override.php');
}

// Add required parameters
$container->setParameter('mautic.secret_key', '68c7e75470c02cba06dd543431411e0de94e04fdf2b3a2eac05957060edb66d0');
$container->setParameter('mautic.security.disableUpdates', true);
$container->setParameter('mautic.rss_notification_url', null);
$container->setParameter('mautic.batch_sleep_time', 0);

// Turn off creating of indexes in lead field fixtures
$container->register('mautic.install.fixture.lead_field', Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class)
    ->addArgument(new Reference('translator'))
    ->addTag(FixturesCompilerPass::FIXTURE_TAG)
    ->setPublic(true);

if (defined('IS_PHPUNIT')) {
    $container->register('security.csrf.token_storage', Mautic\CoreBundle\Test\Session\InMemoryTokenStorage::class)->setAutowired(true);
}

// Use static namespace for token manager
$container->register('security.csrf.token_manager', Symfony\Component\Security\Csrf\CsrfTokenManager::class)
    ->addArgument(new Reference('security.csrf.token_generator'))
    ->addArgument(new Reference('security.csrf.token_storage'))
    ->addArgument('test')
    ->setPublic(true);

// HTTP client mock handler providing response queue
$container->register(GuzzleHttp\Handler\MockHandler::class)->setPublic(true);

$container->register('http_client', Symfony\Component\HttpClient\MockHttpClient::class)
    ->setPublic(true);

$container->register('test.service_container', Mautic\CoreBundle\Test\Container\TestContainer::class)
    ->setArgument('$kernel', new Reference('kernel'))
    ->setArgument('$privateServicesLocatorId', 'test.private_services_locator')
    ->setPublic(true);
