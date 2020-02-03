<?php

// Include path settings
$root = $container->getParameter('kernel.root_dir');
include __DIR__.'/paths_helper.php';

$ormMappings        =
$serializerMappings =
$ipLookupServices   = [];

//Note Mautic specific bundles so they can be applied as needed without having to specify them individually
$buildBundles = function ($namespace, $bundle) use ($container, $paths, $root, &$ormMappings, &$serializerMappings, &$ipLookupServices) {
    $isPlugin = $isMautic = false;

    if (false !== strpos($namespace, 'MauticPlugin\\')) {
        $isPlugin   = true;
        $bundleBase = $bundle;
        $relative   = $paths['plugins'].'/'.$bundleBase;
    } elseif (false !== strpos($namespace, 'Mautic\\')) {
        $isMautic   = true;
        $bundleBase = str_replace('Mautic', '', $bundle);
        $relative   = $paths['bundles'].'/'.$bundleBase;
    }

    if ($isMautic || $isPlugin) {
        $baseNamespace = preg_replace('#\\\[^\\\]*$#', '', $namespace);
        $directory     = $paths['root'].'/'.$relative;

        // Check for a single config file
        $config = (file_exists($directory.'/Config/config.php')) ? include $directory.'/Config/config.php' : [];

        // Remove optional services (has argument optional = true) if the service class does not exist
        if (isset($config['services'])) {
            $config['services'] = (new \Tightenco\Collect\Support\Collection($config['services']))
                ->mapWithKeys(function (array $serviceGroup, string $groupName) {
                    $serviceGroup = (new \Tightenco\Collect\Support\Collection($serviceGroup))
                        ->reject(function ($serviceDefinition) {
                            // Rejects services defined as optional where the service class does not exist.
                            return is_array($serviceDefinition)
                                && isset($serviceDefinition['optional'])
                                && true === $serviceDefinition['optional']
                                && isset($serviceDefinition['class'])
                                && false === class_exists($serviceDefinition['class']);
                        })->toArray();

                    return [$groupName => $serviceGroup];
                })->toArray();
        }

        // Services need to have percent signs escaped to prevent ParameterCircularReferenceException
        if (isset($config['services'])) {
            array_walk_recursive(
                $config['services'],
                function (&$v, $k) {
                    $v = str_replace('%', '%%', $v);
                }
            );
        }

        // Register IP lookup services
        if (isset($config['ip_lookup_services'])) {
            $ipLookupServices = array_merge($ipLookupServices, $config['ip_lookup_services']);
        }

        // Check for staticphp mapping
        if (file_exists($directory.'/Entity')) {
            $finder = \Symfony\Component\Finder\Finder::create()->files('*.php')->in($directory.'/Entity')->notName('*Repository.php');

            foreach ($finder as $file) {
                // Check to see if entities are organized by subfolder
                $subFolder = $file->getRelativePath();

                // Just check first file for the loadMetadata function
                $reflectionClass = new \ReflectionClass('\\'.$baseNamespace.'\\Entity\\'.(!empty($subFolder) ? $subFolder.'\\' : '').basename($file->getFilename(), '.php'));

                if (!$reflectionClass->implementsInterface(\Mautic\CoreBundle\Entity\DeprecatedInterface::class)) {
                    // Register API metadata
                    if ($reflectionClass->hasMethod('loadApiMetadata')) {
                        $serializerMappings[$bundle] = [
                            'namespace_prefix' => $baseNamespace.'\\Entity',
                            'path'             => "@$bundle/Entity",
                        ];
                    }

                    // Register entities
                    if ($reflectionClass->hasMethod('loadMetadata')) {
                        $ormMappings[$bundle] = [
                            'dir'       => 'Entity',
                            'type'      => 'staticphp',
                            'prefix'    => $baseNamespace.'\\Entity',
                            'mapping'   => true,
                            'is_bundle' => true,
                        ];
                    }
                }
            }
        }

        // Build permission object lists
        // @todo - convert to tagged services
        $permissionClasses = [];
        if (file_exists($directory.'/Security/Permissions')) {
            $finder = \Symfony\Component\Finder\Finder::create()->files('*Permissions.php')->in($directory.'/Security/Permissions');

            foreach ($finder as $file) {
                $className       = basename($file->getFilename(), '.php');
                $permissionClass = '\\'.$baseNamespace.'\\Security\\Permissions\\'.$className;
                // Skip CorePermissions and AbstractPermissions
                if ('CoreBundle' === $bundleBase && in_array($className, ['CorePermissions', 'AbstractPermissions'])) {
                    continue;
                }

                $permissionInstance = new $permissionClass([]);
                $permissionName     = $permissionInstance->getName();

                $permissionClasses[$permissionName] = $permissionClass;
            }
        }

        return [
            'isPlugin'          => $isPlugin,
            'base'              => str_replace('Bundle', '', $bundleBase),
            'bundle'            => $bundleBase,
            'namespace'         => $baseNamespace,
            'symfonyBundleName' => $bundle,
            'bundleClass'       => $namespace,
            'permissionClasses' => $permissionClasses,
            'relative'          => $relative,
            'directory'         => $directory,
            'config'            => $config,
        ];
    }

    return false;
};

// Separate out Mautic's bundles from other Symfony bundles
$symfonyBundles = $container->getParameter('kernel.bundles');
$mauticBundles  = array_filter(
    array_map($buildBundles, $symfonyBundles, array_keys($symfonyBundles)),
    function ($v) {
        return !empty($v);
    }
);
unset($buildBundles);

// Load extra annotations
$container->loadFromExtension('sensio_framework_extra', [
    'router'  => ['annotations' => false],
    'request' => ['converters' => false],
    'view'    => ['annotations' => true],
    'cache'   => ['annotations' => false],
]);

// Sort Mautic's bundles into Core and Plugins
$setBundles = $setPluginBundles = [];
foreach ($mauticBundles as $bundle) {
    if ($bundle['isPlugin']) {
        $setPluginBundles[$bundle['symfonyBundleName']] = $bundle;
    } else {
        $setBundles[$bundle['symfonyBundleName']] = $bundle;
    }
}

// Make Core the first in the list
$coreBundle = $setBundles['MauticCoreBundle'];
unset($setBundles['MauticCoreBundle']);
$setBundles = array_merge(['MauticCoreBundle' => $coreBundle], $setBundles);

$container->setParameter('mautic.bundles', $setBundles);
$container->setParameter('mautic.plugin.bundles', $setPluginBundles);
unset($setBundles, $setPluginBundles);

// Set IP lookup services
$container->setParameter('mautic.ip_lookup_services', $ipLookupServices);

// Load parameters
include __DIR__.'/parameters.php';
$container->loadFromExtension('mautic_core');
$configParameterBag = (new \Mautic\CoreBundle\Loader\ParameterLoader())->getParameterBag();

// Set template engines
$engines = ['php', 'twig'];

// Decide on secure cookie based on site_url setting
// This cannot be set dynamically
$siteUrl      = $configParameterBag->get('site_url');
$secureCookie = ($siteUrl && 0 === strpos($siteUrl, 'https'));

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
        'array'    => 'Mautic\CoreBundle\Doctrine\Type\ArrayType',
        'datetime' => 'Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType',
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
        'mappings'                    => $ormMappings,
    ],
]);

//MigrationsBundle Configuration
$container->loadFromExtension('doctrine_migrations', [
    'dir_name'   => '%kernel.root_dir%/migrations',
    'namespace'  => 'Mautic\\Migrations',
    'table_name' => '%env(MAUTIC_MIGRATIONS_TABLE_NAME)%',
    'name'       => 'Mautic Migrations',
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
        'directories'    => $serializerMappings,
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

$rateLimit = $configParameterBag->get('api_rate_limiter_limit');
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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter(
    'console_exception_listener.class',
    'Mautic\CoreBundle\EventListener\ConsoleExceptionListener'
);
$definitionConsoleExceptionListener = new Definition(
    '%console_exception_listener.class%',
    [new Reference('monolog.logger.mautic')]
);
$definitionConsoleExceptionListener->addTag(
    'kernel.event_listener',
    ['event' => 'console.exception']
);
$container->setDefinition(
    'mautic.kernel.listener.command_exception',
    $definitionConsoleExceptionListener
);

$container->setParameter(
    'console_terminate_listener.class',
    'Mautic\CoreBundle\EventListener\ConsoleTerminateListener'
);
$definitionConsoleExceptionListener = new Definition(
    '%console_terminate_listener.class%',
    [new Reference('monolog.logger.mautic')]
);
$definitionConsoleExceptionListener->addTag(
    'kernel.event_listener',
    ['event' => 'console.terminate']
);
$container->setDefinition(
    'mautic.kernel.listener.command_terminate',
    $definitionConsoleExceptionListener
);

// ElFinder File Manager
$elFinderPath = trim($container->getParameter('mautic.image_path'), '/');
$elFinderUrl  = rtrim($container->getParameter('mautic.site_url'), '/').'/'.$elFinderPath;

$container->loadFromExtension('fm_elfinder', [
    'assets_path'            => 'media/assets',
    'instances'              => [
        'default' => [
            'locale'          => 'LANG',
            'editor'          => 'custom',
            'editor_template' => '@bundles/CoreBundle/Assets/js/libraries/filemanager/index.html.twig',
            'fullscreen'      => true,
            'include_assets'  => true,
            'relative_path'   => false,
            'connector'       => [
                'roots' => [
                    'uploads' => [
                        'driver'            => 'LocalFileSystem',
                        'path'              => $elFinderPath,
                        'upload_allow'      => ['image/png', 'image/jpg', 'image/jpeg'],
                        'upload_deny'       => ['all'],
                        'upload_max_size'   => '2M',
                        'accepted_name'     => '/^[\w\x{0300}-\x{036F}][\w\x{0300}-\x{036F}\s\.\%\-]*$/u', // Supports diacritic symbols
                        'url'               => $elFinderUrl, // We need to specify URL in case mod_rewrite is disabled
                    ],
                ],
            ],
        ],
    ],
]);
