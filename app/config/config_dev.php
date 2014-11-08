<?php
$loader->import("config.php");
$loader->import("security.php");

$container->loadFromExtension('framework', array(
    "router"   => array(
        "resource"            => "%kernel.root_dir%/config/routing_dev.php",
        "strict_requirements" => true
    ),
    "profiler" => array(
        "only_exceptions" => false
    )
));

$container->loadFromExtension("web_profiler", array(
    "toolbar"             => true,
    "intercept_redirects" => false
));

$container->loadFromExtension("monolog", array(
    'channels' => array(
        'mautic',
    ),
    "handlers" => array(
        "main"    => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/%kernel.environment%.php",
            "level" => "debug",
            "channels" => array(
                "!mautic"
            )
        ),
        "console" => array(
            "type"   => "console",
            "bubble" => false
        ),
        "mautic"    => array(
            "type"  => "stream",
            "path"  => "%kernel.logs_dir%/mautic_%kernel.environment%.php",
            "level" => "debug",
            'channels' => array(
                'mautic',
            )
        )
        // uncomment to get logging in your browser
        // you may have to allow bigger header sizes in your Web server configuration
        /*
        "firephp" => array(
            "type"  => "firephp",
            "level" => "info"
        ),
        "chromephp" => array(
            "type"  => "chromephp",
            "level" => "info"
        ),
        */
    )
));

if ($container->getParameter('mautic.api_enabled')) {
    //Load API doc
    $container->loadFromExtension('nelmio_api_doc', array());
}

//Register command line logging
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container->setParameter(
    'console_exception_listener.class',
    'Mautic\CoreBundle\EventListener\ConsoleExceptionListener'
);
$definitionConsoleExceptionListener = new Definition(
    '%console_exception_listener.class%',
    array(new Reference('logger'))
);
$definitionConsoleExceptionListener->addTag(
    'kernel.event_listener',
    array('event' => 'console.exception')
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
    array(new Reference('logger'))
);
$definitionConsoleExceptionListener->addTag(
    'kernel.event_listener',
    array('event' => 'console.terminate')
);
$container->setDefinition(
    'mautic.kernel.listener.command_terminate',
    $definitionConsoleExceptionListener
);


if (file_exists(__DIR__ . '/config_local.php')) {
    //to allow local settings without committing to git such as swift mailer delivery address overrides
    $loader->import("config_local.php");
}