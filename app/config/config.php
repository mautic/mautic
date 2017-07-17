<?php

include __DIR__.'/paths_helper.php';

$ormMappings        =
$serializerMappings =
$ipLookupServices   = [];

//Note Mautic specific bundles so they can be applied as needed without having to specify them individually
$buildBundles = function ($namespace, $bundle) use ($container, $paths, $root, &$ormMappings, &$serializerMappings, &$ipLookupServices) {
    $isPlugin = $isMautic = false;

    if (strpos($namespace, 'MauticPlugin\\') !== false) {
        $isPlugin   = true;
        $bundleBase = $bundle;
        $relative   = $paths['plugins'].'/'.$bundleBase;
    } elseif (strpos($namespace, 'Mautic\\') !== false) {
        $isMautic   = true;
        $bundleBase = str_replace('Mautic', '', $bundle);
        $relative   = $paths['bundles'].'/'.$bundleBase;
    }

    if ($isMautic || $isPlugin) {
        $baseNamespace = preg_replace('#\\\[^\\\]*$#', '', $namespace);
        $directory     = $paths['root'].'/'.$relative;

        // Check for a single config file
        $config = (file_exists($directory.'/Config/config.php')) ? include $directory.'/Config/config.php' : [];

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

// Set template engines
$engines = ['php', 'twig'];

// Decide on secure cookie based on site_url setting
$secureCookie = isset($parameters['site_url']) && substr(ltrim($parameters['site_url']), 0, 5) === 'https';

// Generate session name
$key         = isset($parameters['secret_key']) ? $parameters['secret_key'] : uniqid();
$sessionName = md5(md5($paths['local_config']).$key);

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
    'trusted_hosts'   => '%mautic.trusted_hosts%',
    'trusted_proxies' => '%mautic.trusted_proxies%',
    'session'         => [ //handler_id set to null will use default session handler from php.ini
        'handler_id'    => null,
        'name'          => $sessionName,
        'cookie_secure' => $secureCookie,
    ],
    'fragments'            => null,
    'http_method_override' => true,

    /*'validation'           => array(
        'static_method' => array('loadValidatorMetadata')
    )*/
]);

//Doctrine Configuration
$dbalSettings = [
    'driver'   => '%mautic.db_driver%',
    'host'     => '%mautic.db_host%',
    'port'     => '%mautic.db_port%',
    'dbname'   => '%mautic.db_name%',
    'user'     => '%mautic.db_user%',
    'password' => '%mautic.db_password%',
    'charset'  => 'UTF8',
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

// If using pdo_sqlite as the database driver, add the path to config file
$dbDriver = $container->getParameter('mautic.db_driver');
if ($dbDriver == 'pdo_sqlite') {
    $dbalSettings['path'] = '%mautic.db_path%';
}

$container->loadFromExtension('doctrine', [
    'dbal' => $dbalSettings,
    'orm'  => [
        'auto_generate_proxy_classes' => '%kernel.debug%',
        'auto_mapping'                => true,
        'mappings'                    => $ormMappings,
    ],
]);

//MigrationsBundle Configuration
$prefix = $container->getParameter('mautic.db_table_prefix');
$container->loadFromExtension('doctrine_migrations', [
    'dir_name'   => '%kernel.root_dir%/migrations',
    'namespace'  => 'Mautic\\Migrations',
    'table_name' => $prefix.'migrations',
    'name'       => 'Mautic Migrations',
]);

// Swiftmailer Configuration
$mailerSettings = [
    'transport'  => '%mautic.mailer_transport%',
    'host'       => '%mautic.mailer_host%',
    'port'       => '%mautic.mailer_port%',
    'username'   => '%mautic.mailer_user%',
    'password'   => '%mautic.mailer_password%',
    'encryption' => '%mautic.mailer_encryption%',
    'auth_mode'  => '%mautic.mailer_auth_mode%',
];

// Only spool if using file as otherwise emails are not sent on redirects
$spoolType = $container->getParameter('mautic.mailer_spool_type');
if ($spoolType == 'file') {
    $mailerSettings['spool'] = [
        'type' => '%mautic.mailer_spool_type%',
        'path' => '%mautic.mailer_spool_path%',
    ];
}
$container->loadFromExtension('swiftmailer', $mailerSettings);

//KnpMenu Configuration
$container->loadFromExtension('knp_menu', [
    'twig'             => false,
    'templating'       => true,
    'default_renderer' => 'mautic',
]);

// OneupUploader Configuration
$uploadDir = $container->getParameter('mautic.upload_dir');
$maxSize   = $container->getParameter('mautic.max_size');
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
                'directory' => $uploadDir,
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
        'json' => [
            'options' => JSON_PRETTY_PRINT,
        ],
    ],
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
