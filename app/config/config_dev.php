<?php
$loader->import("config.php");

if (file_exists(__DIR__ . '/security_local.php')) {
    $loader->import("security_local.php");
} else {
    $loader->import("security.php");
}

//Twig Configuration
$container->loadFromExtension('twig', array(
    'debug'                => '%kernel.debug%',
    'strict_variables'     => '%kernel.debug%'
));

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
            "type"  => "rotating_file",
            "path"  => "%kernel.logs_dir%/%kernel.environment%.php",
            "level" => "debug",
            "channels" => array(
                "!mautic"
            ),
            "max_files" => 7
        ),
        "console" => array(
            "type"   => "console",
            "bubble" => false
        ),
        "mautic"    => array(
            "type"  => "rotating_file",
            "path"  => "%kernel.logs_dir%/mautic_%kernel.environment%.php",
            "level" => "debug",
            'channels' => array(
                'mautic',
            ),
            "max_files" => 7
        )
    )
));

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

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists(__DIR__ . '/config_override.php')) {
    $loader->import("config_override.php");
}

// Allow local settings without committing to git such as swift mailer delivery address overrides
if (file_exists(__DIR__ . '/config_local.php')) {
    $loader->import("config_local.php");
}