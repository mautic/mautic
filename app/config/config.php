<?php

use Mautic\CoreBundle\EventListener\ConsoleErrorListener;
use Mautic\CoreBundle\EventListener\ConsoleTerminateListener;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/** @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */

// Include path settings
$root = $container->getParameter('kernel.root_dir');

/** @var array $paths */
include __DIR__.'/paths_helper.php';

// Load extra annotations
$container->loadFromExtension('sensio_framework_extra', [
    'router'  => ['annotations' => false],
    'request' => ['converters' => false],
    'view'    => ['annotations' => true],
    'cache'   => ['annotations' => false],
]);

// Build and store Mautic bundle metadata
$symfonyBundles        = $container->getParameter('kernel.bundles');
$bundleMetadataBuilder = new \Mautic\CoreBundle\DependencyInjection\Builder\BundleMetadataBuilder($symfonyBundles, $paths, $root);

$container->setParameter('mautic.bundles', $bundleMetadataBuilder->getCoreBundleMetadata());
$container->setParameter('mautic.plugin.bundles', $bundleMetadataBuilder->getPluginMetadata());

// Set IP lookup services
$container->setParameter('mautic.ip_lookup_services', $bundleMetadataBuilder->getIpLookupServices());

// Load parameters
include __DIR__.'/parameters.php';
$container->loadFromExtension('mautic_core');
$configParameterBag = (new \Mautic\CoreBundle\Loader\ParameterLoader())->getParameterBag();

// Set template engines
$engines = ['php', 'twig'];

// Decide on secure cookie based on site_url setting or the request if in installer
// This cannot be set dynamically

if (defined('MAUTIC_INSTALLER')) {
    $request      = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $secureCookie = $request->isSecure();
} else {
    $siteUrl      = $configParameterBag->get('site_url');
    $secureCookie = ($siteUrl && 0 === strpos($siteUrl, 'https'));
}

$container->loadFromExtension('framework', [
    'secret' => '%mautic.secret_key%',
    'router' => [
        'resource'            => '%kernel.root_dir%/config/routing.php',
        'strict_requirements' => null,
    ],
    'form'            => null,
    'csrf_protection' => true,
    'validation'      => [
        'enable_annotations' => false,
    ],
    'templating' => [
        'engines' => $engines,
        'form'    => [
            'resources' => [
                'MauticCoreBundle:FormTheme\\Custom',
            ],
        ],
    ],
    'default_locale' => '%mautic.locale%',
    'translator'     => [
        'enabled'  => true,
        'fallback' => 'en_US',
    ],
    'session'         => [ //handler_id set to null will use default session handler from php.ini
        'handler_id'    => null,
        'name'          => '%env(MAUTIC_SESSION_NAME)%',
        'cookie_secure' => $secureCookie,
    ],
    'fragments'            => null,
    'http_method_override' => true,

    /*'validation'           => array(
        'static_method' => array('loadValidatorMetadata')
    )*/
]);

$container->setParameter('mautic.famework.csrf_protection', true);

//Doctrine Configuration
$dbalSettings = [
    'driver'                => '%mautic.db_driver%',
    'host'                  => '%mautic.db_host%',
    'port'                  => '%mautic.db_port%',
    'dbname'                => '%mautic.db_name%',
    'user'                  => '%mautic.db_user%',
    'password'              => '%mautic.db_password%',
    'charset'               => 'utf8mb4',
    'default_table_options' => [
        'charset'    => 'utf8mb4',
        'collate'    => 'utf8mb4_unicode_ci',
        'row_format' => 'DYNAMIC',
    ],
    'types'    => [
        'array'     => \Mautic\CoreBundle\Doctrine\Type\ArrayType::class,
        'datetime'  => \Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType::class,
        'generated' => \Mautic\CoreBundle\Doctrine\Type\GeneratedType::class,
    ],
    // Prevent Doctrine from crapping out with "unsupported type" errors due to it examining all tables in the database and not just Mautic's
    'mapping_types' => [
        'enum'  => 'string',
        'point' => 'string',
        'bit'   => 'string',
    ],
    'server_version' => '%mautic.db_server_version%',
];

$container->loadFromExtension('doctrine', [
    'dbal' => $dbalSettings,
    'orm'  => [
        'auto_generate_proxy_classes' => '%kernel.debug%',
        'auto_mapping'                => true,
        'mappings'                    => $bundleMetadataBuilder->getOrmConfig(),
    ],
]);

//MigrationsBundle Configuration
$container->loadFromExtension('doctrine_migrations', [
    'dir_name'        => '%kernel.root_dir%/migrations',
    'namespace'       => 'Mautic\\Migrations',
    'table_name'      => '%env(MAUTIC_MIGRATIONS_TABLE_NAME)%',
    'name'            => 'Mautic Migrations',
    'custom_template' => '%kernel.root_dir%/migrations/Migration.template',
]);

// Swiftmailer Configuration
$container->loadFromExtension('swiftmailer', [
    'transport'  => '%mautic.mailer_transport%',
    'host'       => '%mautic.mailer_host%',
    'port'       => '%mautic.mailer_port%',
    'username'   => '%mautic.mailer_user%',
    'password'   => '%mautic.mailer_password%',
    'encryption' => '%mautic.mailer_encryption%',
    'auth_mode'  => '%mautic.mailer_auth_mode%',
    'spool'      => [
        'type' => 'service',
        'id'   => 'mautic.transport.spool',
    ],
]);

//KnpMenu Configuration
$container->loadFromExtension('knp_menu', [
    'twig'             => false,
    'templating'       => true,
    'default_renderer' => 'mautic',
]);

// OneupUploader Configuration
$container->loadFromExtension('oneup_uploader', [
    // 'orphanage' => array(
    //     'maxage' => 86400,
    //     'directory' => $uploadDir . '/orphanage'
    // ),
    'mappings' => [
        'asset' => [
            'error_handler'   => 'mautic.asset.upload.error.handler',
            'frontend'        => 'custom',
            'custom_frontend' => [
                'class' => 'Mautic\AssetBundle\Controller\UploadController',
                'name'  => 'mautic',
            ],
            // 'max_size' => ($maxSize * 1000000),
            // 'use_orphanage' => true,
            'storage' => [
                'directory' => '%mautic.upload_dir%',
            ],
        ],
    ],
]);

//FOS Rest for API
$container->loadFromExtension('fos_rest', [
    'routing_loader' => [
        'default_format' => 'json',
        'include_format' => false,
    ],
    'view' => [
        'formats' => [
            'json' => true,
            'xml'  => false,
            'html' => false,
        ],
        'templating_formats' => [
            'html' => false,
        ],
    ],
    'disable_csrf_role' => 'ROLE_API',
]);

//JMS Serializer for API and Webhooks
$container->loadFromExtension('jms_serializer', [
    'handlers' => [
        'datetime' => [
            'default_format'   => 'c',
            'default_timezone' => 'UTC',
        ],
    ],
    'property_naming' => [
        'separator'  => '',
        'lower_case' => false,
    ],
    'metadata' => [
        'cache'          => 'none',
        'auto_detection' => false,
        'directories'    => $bundleMetadataBuilder->getSerializerConfig(),
    ],
    'visitors' => [
        'json_deserialization' => [
            'options' => JSON_PRETTY_PRINT,
        ],
    ],
]);

$container->loadFromExtension('framework', [
    'cache' => [
        'pools' => [
            'api_rate_limiter_cache' => $configParameterBag->get('api_rate_limiter_cache'),
        ],
    ],
]);

$rateLimit = (int) $configParameterBag->get('api_rate_limiter_limit');
$container->loadFromExtension('noxlogic_rate_limit', [
  'enabled'        => 0 === $rateLimit ? false : true,
  'storage_engine' => 'cache',
  'cache_service'  => 'api_rate_limiter_cache',
  'path_limits'    => [
    [
      'path'   => '/api',
      'limit'  => $rateLimit,
      'period' => 3600,
    ],
  ],
  'fos_oauth_key_listener' => true,
  'display_headers'        => true,
  'rate_response_message'  => '{ "errors": [ { "code": 429, "message": "You exceeded the rate limit of '.$rateLimit.' API calls per hour.", "details": [] } ]}',
]);

$container->setParameter(
    'jms_serializer.camel_case_naming_strategy.class',
    'JMS\Serializer\Naming\IdenticalPropertyNamingStrategy'
);

// Monolog formatter
$container->register('mautic.monolog.fulltrace.formatter', 'Monolog\Formatter\LineFormatter')
    ->addMethodCall('includeStacktraces', [true])
    ->addMethodCall('ignoreEmptyContextAndExtra', [true]);

//Register command line logging
$container->setParameter(
    'console_error_listener.class',
    ConsoleErrorListener::class
);
$definitionConsoleErrorListener = new Definition(
    '%console_error_listener.class%',
    [new Reference('monolog.logger.mautic')]
);
$definitionConsoleErrorListener->addTag(
    'kernel.event_listener',
    ['event' => 'console.error']
);
$container->setDefinition(
    'mautic.kernel.listener.command_exception',
    $definitionConsoleErrorListener
);

$container->setParameter(
    'console_terminate_listener.class',
    ConsoleTerminateListener::class
);
$definitionConsoleErrorListener = new Definition(
    '%console_terminate_listener.class%',
    [new Reference('monolog.logger.mautic')]
);
$definitionConsoleErrorListener->addTag(
    'kernel.event_listener',
    ['event' => 'console.terminate']
);
$container->setDefinition(
    'mautic.kernel.listener.command_terminate',
    $definitionConsoleErrorListener
);

// ElFinder File Manager
$container->loadFromExtension('fm_elfinder', [
    'assets_path' => 'media/assets',
    'instances'   => [
        'default' => [
            'locale'          => 'LANG',
            'editor'          => 'custom',
            'editor_template' => '@bundles/CoreBundle/Assets/js/libraries/filemanager/index.html.twig',
            'fullscreen'      => true,
            //'include_assets'  => true,
            'relative_path'   => false,
            'connector'       => [
                'debug' => '%kernel.debug%',
                'roots' => [
                    'local' => [
                        'driver'    => 'Flysystem',
                        'path'      => '',
                        'flysystem' => [
                            'type'    => 'local',
                            'options' => [
                                'local' => [
                                    'path' => '%env(resolve:MAUTIC_EL_FINDER_PATH)%',
                                ],
                            ],
                        ],
                        'upload_allow'  => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                        'upload_deny'   => ['all'],
                        'accepted_name' => '/^[\w\x{0300}-\x{036F}][\w\x{0300}-\x{036F}\s\.\%\-]*$/u', // Supports diacritic symbols
                        'url'           => '%env(resolve:MAUTIC_EL_FINDER_URL)%', // We need to specify URL in case mod_rewrite is disabled
                        'tmb_path'      => '%env(resolve:MAUTIC_EL_FINDER_PATH)%/.tmb/',
                        'tmb_url'       => '%env(resolve:MAUTIC_EL_FINDER_URL)%/.tmb/',
                    ],
                ],
            ],
        ],
    ],
]);
