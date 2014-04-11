<?php
//Note Mautic specific bundles so they can be applied as needed without having to specify them individually
$mauticbundles = array_filter(
    $container->getParameter('kernel.bundles'),
    function($v) {
        return ((strpos($v, "Mautic") !== false) ? 1 : 0);
    }
);
$container->setParameter("mautic.bundles", $mauticbundles);

$loader->import("parameters.php");
$loader->import("security.php");

$container->loadFromExtension("framework", array(
    "secret"               => "%mautic.secret%",
    "router"               => array(
        "resource"            => "%kernel.root_dir%/config/routing.php",
        "strict_requirements" => null,
    ),
    "form"                 => null,
    "csrf_protection"      => true,
    "validation"           => array(
        "enable_annotations" => true
    ),
    "templating"           => array(
        "engines" => array(
            "twig",
            "php"
        ),
        'form' => array(
            'resources' => array(
                'MauticCoreBundle:Form',
            ),
        ),
        /*
        "assets_base_urls" => array(
            "http" => array("/media/"),
            "ssl"  => array("/media/")
        )
        */
    ),
    "default_locale"       => "%mautic.locale%",
    "translator"           => array(
        "enabled"  => true,
        "fallback" => "en"
    ),
    "trusted_hosts"        => "%mautic.trusted_hosts%",
    "trusted_proxies"      => "%mautic.trusted_proxies%",
    "session"              => array( //handler_id set to null will use default session handler from php.ini
        "handler_id" => null
    ),
    "fragments"            => null,
    "http_method_override" => true
));

//Twig Configuration
$container->loadFromExtension("twig", array(
    "debug"            => "%kernel.debug%",
    "strict_variables" => "%kernel.debug%",
));

//Doctrine Configuration
$container->loadFromExtension("doctrine", array(
    "dbal" => array(
        "driver"   => "%mautic.db_driver%",
        "host"     => "%mautic.db_host%",
        "port"     => "%mautic.db_port%",
        "dbname"   => "%mautic.db_name%",
        "user"     => "%mautic.db_user%",
        "password" => "%mautic.db_password%",
        "charset"  => "UTF8",
        //if using pdo_sqlite as your database driver, add the path in parameters.php
        //e.g. "database_path" => "%kernel.root_dir%/data/data.db3"
        //"path"    => "%db_path%"
    ),

    "orm"  => array(
        "auto_generate_proxy_classes" => "%kernel.debug%",
        "auto_mapping"                => true
    )
));



//Swiftmailer Configuration
$container->loadFromExtension("swiftmailer", array(
    "transport" => "%mautic.mailer_transport%",
    "host"      => "%mautic.mailer_host%",
    "username"  => "%mautic.mailer_user%",
    "password"  => "%mautic.mailer_password%",
    "spool"     => array(
        "type" => "memory"
    )
));


//Also Assetic does not allow variables when loading resources in templates because it renders the media at compilation time and thus
//the appropriate variables are not populated.  In order to not have to manually add each bundles' media files to
//MauticBaseBundle's base.html.php file, we are doing the following which works because of Symfony's caching.

//For production, you must dump the assets via php app/console assetic:dump --env=prod

$css    = array();
$js     = array();

foreach ($mauticbundles as $bundle => $namespace) {
    //parse the namespace into a filepath
    $namespaceParts = explode("\\", $namespace);
    $bundleDir      = __DIR__ . "/../../src/" . $namespaceParts[0] . "/" . $namespaceParts[1];

    //define the function for use with CSS and JS files
    $getFiles = function ($type) use ($bundleDir, &$css, &$js) {
        $typeDir = "$bundleDir/Resources/public/$type/";

        if (file_exists($typeDir)) {
            //get files within the directory
            $iterator = new FilesystemIterator($typeDir);
            //filter out inappropriate files
            $filter = new RegexIterator($iterator, "/.$type$/");
            if (iterator_count($filter)) {
                foreach ($filter as $file) {
                    //add the file to be loaded
                    ${$type}[] = $file->getPathname();
                }
            }
        }
    };

    $getFiles("css");
    $getFiles("js");
}

//Assetic Configuration
$container->loadFromExtension("assetic", array(
    "debug"          => "%kernel.debug%",
    "use_controller" => false,
    "read_from"      => "%kernel.root_dir%/../../",
    "write_to"       => "%kernel.root_dir%/../../",
    "filters"        => array(
        "cssrewrite" => array(
            'apply_to' => '\.css$',
        )
    ),
    "assets"         => array(
        "mautic_stylesheets" => array(
            "inputs"  => $css,
            "options" => array(
                "combine" => true,
                //"output"  => 'css/mautic.css'
            )
        ),
        "mautic_javascripts" => array(
            "inputs"  => $js,
            "options" => array(
                "combine" => true,
                //"output"  => 'js/mautic.js'
            )
        )
    )
));

//KnpMenu Configuration
$container->loadFromExtension("knp_menu", array(
    "twig" => false,
    "templating" => true,
    "default_renderer" => "mautic"
));