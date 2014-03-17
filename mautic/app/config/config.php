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
$loader->import("services.php");

$container->loadFromExtension("framework", array(
    "secret"               => "%secret%",
    "router"               => array(
        "resource"            => "%kernel.root_dir%/config/routing.php",
        "strict_requirements" => null,
    ),
    "form"                 => null,
    "csrf_protection"      => null,
    "validation"           => array(
        "enable_annotations" => true
    ),
    "templating"           => array(
        "engines" => array(
            "twig",
            "php"
        ),
        "assets_base_urls" => array(
            "http" => array("media/"),
            "ssl"  => array("media/")
        )
    ),
    "default_locale"       => "%locale%",
    "trusted_hosts"        => null,
    "trusted_proxies"      => null,
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
        "driver"   => "%database_driver%",
        "host"     => "%database_host%",
        "port"     => "%database_port%",
        "dbname"   => "%database_name%",
        "user"     => "%database_user%",
        "password" => "%database_password%",
        "charset"  => "UTF8",
        //if using pdo_sqlite as your database driver, add the path in parameters.php
        //e.g. "database_path" => "%kernel.root_dir%/data/data.db3"
        //"path"    => "%database_path%"
    ),

    "orm"  => array(
        "auto_generate_proxy_classes" => "%kernel.debug%",
        "auto_mapping"                => true
    )
));

//Swiftmailer Configuration
$container->loadFromExtension("swiftmailer", array(
    "transport" => "%mailer_transport%",
    "host"      => "%mailer_host%",
    "username"  => "%mailer_user%",
    "password"  => "%mailer_password%",
    "spool"     => array(
        "type" => "memory"
    )
));


//Assetic Configuration

//Assetic does not allow variables when loading resources in templates because it renders the media at compilation time and thus
//the appropriate variables are not populated.  In order to not have to manually add each bundles' media files to
//MauticBaseBundle's base.html.php file, we are doing the following.

//For production, you must dump the assets via php app/console assetic:dump --env=prod

$css = array();
$js  = array();
foreach ($mauticbundles as $bundle => $namespace) {
    //parse the namespace into a filepath
    $namespaceParts = explode("\\", $namespace);
    $bundleDir      = __DIR__ . "/../../src/" . $namespaceParts[0] . "/" . $namespaceParts[1];

    //define the function for use with CSS and JS files
    $getFiles = function ($type) use ($bundleDir, &$css, &$js) {
        $typeDir = "$bundleDir/Resources/public/$type/";

        //get files within the directory
        $iterator = new FilesystemIterator($typeDir);
        //filter out inappropriate files
        $filter   = new RegexIterator($iterator, "/.$type$/");
        if (iterator_count($filter)) {
            foreach ($filter as $file) {
                //add the file to be loaded
                ${$type}[] = $file->getPathname();
            }
        }
    };

    $getFiles("css");
    $getFiles("js");
}

$container->loadFromExtension("assetic", array(
    "debug"          => "%kernel.debug%",
    "use_controller" => "%kernel.debug%",
    "read_from"      => "%kernel.root_dir%/../../media/",
    "write_to"       => "%kernel.root_dir%/../../media/",
    "filters"        => array(
        "cssrewrite" => array(
            "apply_to" => "\\.css$"
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
