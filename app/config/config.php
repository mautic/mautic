<?php
//Note Mautic specific bundles so they can be applied as needed without having to specify them individually
$buildBundles = function($namespace, $bundle) use ($container) {
    if (strpos($namespace, 'Mautic\\') !== false) {
        $bundleBase = str_replace('Mautic', '', $bundle);

        return array(
            "base"              => str_replace('Bundle', '', $bundleBase),
            "bundle"            => $bundleBase,
            "namespace"         => preg_replace('#\\\[^\\\]*$#', '', $namespace),
            "symfonyBundleName" => $bundle,
            "bundleClass"       => $namespace,
            "relative"          => basename($container->getParameter('kernel.root_dir')) . '/bundles/' . $bundleBase,
            "directory"         => $container->getParameter('kernel.root_dir') . '/bundles/' . $bundleBase
        );
    }
    return false;
};

// Note MauticAddon bundles so they can be applied as needed
$buildAddonBundles = function($namespace, $bundle) use ($container) {
    if (strpos($namespace, 'MauticAddon\\') !== false) {
        return array(
            "base"              => str_replace('Bundle', '', $bundle),
            "bundle"            => $bundle,
            "namespace"         => preg_replace('#\\\[^\\\]*$#', '', $namespace),
            "symfonyBundleName" => $bundle,
            "bundleClass"       => $namespace,
            "relative"          => 'addons/' . $bundle,
            "directory"         => dirname($container->getParameter('kernel.root_dir')) . '/addons/' . $bundle
        );
    }
    return false;
};

$symfonyBundles = $container->getParameter('kernel.bundles');

$mauticBundles  = array_filter(
    array_map($buildBundles, $symfonyBundles, array_keys($symfonyBundles)),
    function ($v) { return (!empty($v)); }
);
$addonBundles  = array_filter(
    array_map($buildAddonBundles, $symfonyBundles, array_keys($symfonyBundles)),
    function ($v) { return (!empty($v)); }
);

$setBundles = array();

foreach ($mauticBundles as $bundle) {
    $setBundles[$bundle['symfonyBundleName']] = $bundle;
}
$setAddonBundles = array();
foreach ($addonBundles as $bundle) {
    $setAddonBundles[$bundle['symfonyBundleName']] = $bundle;
}

// Make Core the first in the list
$coreBundle = $setBundles['MauticCoreBundle'];
unset($setBundles['MauticCoreBundle']);
$setBundles = array_merge(array('MauticCoreBundle' => $coreBundle), $setBundles);

$container->setParameter('mautic.bundles', $setBundles);
$container->setParameter('mautic.addon.bundles', $setAddonBundles);

$loader->import('parameters.php');
$container->loadFromExtension('mautic_core');

$container->loadFromExtension('framework', array(
    'secret'               => '%mautic.secret%',
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
        'engines' => array(
            'php'
        ),
        'form' => array(
            'resources' => array(
                'MauticCoreBundle:FormTheme\\Custom',
            ),
        ),
    ),
    'default_locale'       => '%mautic.locale%',
    'translator'           => array(
        'enabled'  => true,
        'fallback' => 'en'
    ),
    'trusted_hosts'        => '%mautic.trusted_hosts%',
    'trusted_proxies'      => '%mautic.trusted_proxies%',
    'session'              => array( //handler_id set to null will use default session handler from php.ini
        'handler_id' => null
    ),
    'fragments'            => null,
    'http_method_override' => true,

    /*'validation'           => array(
        'static_method' => array('loadValidatorMetadata')
    )*/
));

//Doctrine Configuration
$container->loadFromExtension('doctrine', array(
    'dbal' => array(
        'driver'   => '%mautic.db_driver%',
        'host'     => '%mautic.db_host%',
        'port'     => '%mautic.db_port%',
        'dbname'   => '%mautic.db_name%',
        'user'     => '%mautic.db_user%',
        'password' => '%mautic.db_password%',
        'charset'  => 'UTF8',
        //if using pdo_sqlite as your database driver, add the path in parameters.php
        //e.g. 'database_path' => '%kernel.root_dir%/data/data.db3'
        //'path'    => '%db_path%'
        'types'    => array(
            'datetime' => 'Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType'
        )
    ),

    'orm'  => array(
        'auto_generate_proxy_classes' => '%kernel.debug%',
        'auto_mapping'                => true
    )
));

//Swiftmailer Configuration
$container->loadFromExtension('swiftmailer', array(
    'transport' => '%mautic.mailer_transport%',
    'host'      => '%mautic.mailer_host%',
    'port'      => '%mautic.mailer_port%',
    'username'  => '%mautic.mailer_user%',
    'password'  => '%mautic.mailer_password%',
    'spool'     => array(
        'type' => '%mautic.mailer_spool_type%',
        'path' => '%mautic.mailer_spool_path%'
    ),
    'encryption' => '%mautic.mailer_encryption%',
    'auth_mode'  => '%mautic.mailer_auth_mode%'
));

//KnpMenu Configuration
$container->loadFromExtension('knp_menu', array(
    'twig' => false,
    'templating' => true,
    'default_renderer' => 'mautic'
));

//MigrationsBundle Configuration
$container->loadFromExtension('doctrine_migrations', array(
    'dir_name'   => '%kernel.root_dir%/migrations',
    'namespace'  => 'Mautic\\Migrations',
    'table_name' => 'migrations',
    'name'       => 'Mautic Migrations'
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
