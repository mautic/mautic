<?php
//Note Mautic specific bundles so they can be applied as needed without having to specify them individually
$buildBundles = function($namespace, $bundle) use ($container) {
    if (strpos($namespace, 'Mautic\\') !== false) {
        $bundleBase = str_replace('Mautic', '', $bundle);
        $directory  = $container->getParameter('kernel.root_dir') . '/bundles/' . $bundleBase;

        // Check for a single config file
        if (file_exists($directory.'/Config/config.php')) {
            $config = include $directory.'/Config/config.php';
        } else {
            $config = array();
        }

        return array(
            "isPlugin"          => false,
            "base"              => str_replace('Bundle', '', $bundleBase),
            "bundle"            => $bundleBase,
            "namespace"         => preg_replace('#\\\[^\\\]*$#', '', $namespace),
            "symfonyBundleName" => $bundle,
            "bundleClass"       => $namespace,
            "relative"          => basename($container->getParameter('kernel.root_dir')) . '/bundles/' . $bundleBase,
            "directory"         => $directory,
            "config"            => $config
        );
    }
    return false;
};

// Note MauticPlugin bundles so they can be applied as needed
$buildPluginBundles = function($namespace, $bundle) use ($container) {
    // @depracated support for MauticAddon; to be removed in 2.0
    if (strpos($namespace, 'MauticPlugin\\') !== false || strpos($namespace, 'MauticAddon\\') !== false) {
        $directory = dirname($container->getParameter('kernel.root_dir')) . '/plugins/' . $bundle;

        // Check for a single config file
        if (file_exists($directory.'/Config/config.php')) {
            $config = include $directory.'/Config/config.php';
        } else {
            $config = array();
        }

        return array(
            "isPlugin"          => true,
            "base"              => str_replace('Bundle', '', $bundle),
            "bundle"            => $bundle,
            "namespace"         => preg_replace('#\\\[^\\\]*$#', '', $namespace),
            "symfonyBundleName" => $bundle,
            "bundleClass"       => $namespace,
            "relative"          => 'plugins/' . $bundle,
            "directory"         => $directory,
            "config"            => $config
        );
    }
    return false;
};

$symfonyBundles = $container->getParameter('kernel.bundles');

$mauticBundles  = array_filter(
    array_map($buildBundles, $symfonyBundles, array_keys($symfonyBundles)),
    function ($v) { return (!empty($v)); }
);
unset($buildBundles);

$pluginBundles  = array_filter(
    array_map($buildPluginBundles, $symfonyBundles, array_keys($symfonyBundles)),
    function ($v) { return (!empty($v)); }
);
unset($buildPluginBundles, $buildBundles);

$setBundles = array();

foreach ($mauticBundles as $bundle) {
    $setBundles[$bundle['symfonyBundleName']] = $bundle;
}
$setPluginBundles = array();
foreach ($pluginBundles as $bundle) {
    $setPluginBundles[$bundle['symfonyBundleName']] = $bundle;
}

// Make Core the first in the list
$coreBundle = $setBundles['MauticCoreBundle'];
unset($setBundles['MauticCoreBundle']);
$setBundles = array_merge(array('MauticCoreBundle' => $coreBundle), $setBundles);

$container->setParameter('mautic.bundles', $setBundles);
$container->setParameter('mautic.plugin.bundles', $setPluginBundles);
unset($setBundles, $setPluginBundles);

$loader->import('parameters.php');
$container->loadFromExtension('mautic_core');

$engines = ($container->getParameter('kernel.environment') == 'dev') ? array('php', 'twig') : array('php');

if (isset($_COOKIE['mautic_session_name'])) {
    // Attempt to keep from losing sessions if cache is cleared through UI
    $sessionName = $_COOKIE['mautic_session_name'];
} else {
    $paths       = $container->getParameter('mautic.paths');
    $key         = $container->hasParameter('mautic.secret_key') ? $container->getParameter('mautic.secret_key') : uniqid();
    $sessionName = md5(md5($paths['local_config']).$key);
}

$container->loadFromExtension('framework', array(
    'secret'               => '%mautic.secret_key%',
    'router'               => array(
        'resource'            => '%kernel.root_dir%/config/routing.php',
        'strict_requirements' => null,
    ),
    'form'                 => null,
    'csrf_protection'      => true,
    'validation'           => array(
        'enable_annotations' => true
    ),
    'templating'           => array(
        'engines' => $engines,
        'form' => array(
            'resources' => array(
                'MauticCoreBundle:FormTheme\\Custom',
            ),
        ),
    ),
    'default_locale'       => '%mautic.locale%',
    'translator'           => array(
        'enabled'  => true,
        'fallback' => 'en_US'
    ),
    'trusted_hosts'        => '%mautic.trusted_hosts%',
    'trusted_proxies'      => '%mautic.trusted_proxies%',
    'session'              => array( //handler_id set to null will use default session handler from php.ini
        'handler_id' => null,
        'name'       => $sessionName,
    ),
    'fragments'            => null,
    'http_method_override' => true,

    /*'validation'           => array(
        'static_method' => array('loadValidatorMetadata')
    )*/
));

//Doctrine Configuration
$dbalSettings = array(
    'driver'   => '%mautic.db_driver%',
    'host'     => '%mautic.db_host%',
    'port'     => '%mautic.db_port%',
    'dbname'   => '%mautic.db_name%',
    'user'     => '%mautic.db_user%',
    'password' => '%mautic.db_password%',
    'charset'  => 'UTF8',
    'types'    => array(
        'array'    => 'Mautic\CoreBundle\Doctrine\Type\ArrayType',
        'datetime' => 'Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType'
    )
);

// If using pdo_sqlite as the database driver, add the path to config file
$dbDriver = $container->getParameter('mautic.db_driver');
if ($dbDriver == 'pdo_sqlite') {
    $dbalSettings['path'] = '%mautic.db_path%';
}

$container->loadFromExtension('doctrine', array(
    'dbal' => $dbalSettings,
    'orm'  => array(
        'auto_generate_proxy_classes' => '%kernel.debug%',
        'auto_mapping'                => true
    )
));

//MigrationsBundle Configuration
$prefix = $container->getParameter('mautic.db_table_prefix');
$container->loadFromExtension('doctrine_migrations', array(
    'dir_name'   => '%kernel.root_dir%/migrations',
    'namespace'  => 'Mautic\\Migrations',
    'table_name' => $prefix . 'migrations',
    'name'       => 'Mautic Migrations'
));

// Swiftmailer Configuration
$mailerSettings = array(
    'transport'  => '%mautic.mailer_transport%',
    'host'       => '%mautic.mailer_host%',
    'port'       => '%mautic.mailer_port%',
    'username'   => '%mautic.mailer_user%',
    'password'   => '%mautic.mailer_password%',
    'encryption' => '%mautic.mailer_encryption%',
    'auth_mode'  => '%mautic.mailer_auth_mode%'
);

// Only spool if using file as otherwise emails are not sent on redirects
$spoolType = $container->getParameter('mautic.mailer_spool_type');
if ($spoolType == 'file') {
    $mailerSettings['spool'] = array(
        'type' => '%mautic.mailer_spool_type%',
        'path' => '%mautic.mailer_spool_path%'
    );
}
$container->loadFromExtension('swiftmailer', $mailerSettings);

//KnpMenu Configuration
$container->loadFromExtension('knp_menu', array(
    'twig' => false,
    'templating' => true,
    'default_renderer' => 'mautic'
));

// OneupUploader Configuration
$uploadDir = $container->getParameter('mautic.upload_dir');
$maxSize   = $container->getParameter('mautic.max_size');
$container->loadFromExtension('oneup_uploader', array(
    // 'orphanage' => array(
    //     'maxage' => 86400,
    //     'directory' => $uploadDir . '/orphanage'
    // ),
    'mappings' => array(
        'asset' => array(
            'error_handler' => 'mautic.asset.upload.error.handler',
            'frontend' => 'custom',
            'custom_frontend' => array(
                'class' => 'Mautic\AssetBundle\Controller\UploadController',
                'name'  => 'mautic'
            ),
            // 'max_size' => ($maxSize * 1000000),
            // 'use_orphanage' => true,
            'storage'  => array(
                'directory' => $uploadDir
            )
        )
    )
));

if ($container->getParameter('mautic.api_enabled')) {
    //FOS Rest
    $container->loadFromExtension('fos_rest', array(
        'routing_loader' => array(
            'default_format' => 'json',
            'include_format' => false
        ),
        'view'           => array(
            'formats' => array(
                'json' => true,
                'xml'  => false,
                'html' => false
            ),
            'templating_formats' => array(
                'html' => false
            )
        ),
        'disable_csrf_role' => 'ROLE_API'
    ));

    //JMS Serializer
    $container->loadFromExtension('jms_serializer', array(
        'handlers' => array(
            'datetime' => array(
                'default_format' => 'c',
                'default_timezone' => 'UTC'
            )
        ),
        'property_naming' => array(
            'separator'  => '',
            'lower_case' => false
        )
    ));

    $container->setParameter(
        'jms_serializer.camel_case_naming_strategy.class',
        'JMS\Serializer\Naming\IdenticalPropertyNamingStrategy'
    );
}
