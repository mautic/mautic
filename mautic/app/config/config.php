<?php

$loader->import("parameters.php");
$loader->import("security.php");

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


//Assetic Configuration
$container->loadFromExtension("assetic", array(
    "debug"          => "%kernel.debug%",
    "use_controller" => false,
    "bundles"        => array(),
    "filters"        => array(
        "cssrewrite" => null
    )
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
