<?php

use Mautic\CoreBundle\Loader\ParameterLoader;

$root          = $container->getParameter('mautic.application_dir').'/app';
$configBaseDir = ParameterLoader::getLocalConfigBaseDir($root);

$loader->import('config.php');

if (file_exists($configBaseDir.'/config/security_local.php')) {
    $loader->import($configBaseDir.'/config/security_local.php');
} else {
    $loader->import('security.php');
}

// Twig Configuration
$container->loadFromExtension('twig', [
    'cache'            => false,
    'debug'            => '%kernel.debug%',
    'strict_variables' => true,
    'paths'            => [
        '%mautic.application_dir%/app/bundles'                  => 'bundles',
        '%mautic.application_dir%/app/bundles/CoreBundle'       => 'MauticCore',
        '%mautic.application_dir%/themes'                       => 'themes',
    ],
    'form_themes' => [
        // Can be found at bundles/CoreBundle/Resources/views/mautic_form_layout.html.twig
        '@MauticCore/FormTheme/mautic_form_layout.html.twig',
    ],
]);

$container->loadFromExtension('framework', [
    'router' => [
        'resource'            => '%mautic.application_dir%/app/config/routing_dev.php',
        'strict_requirements' => true,
    ],
    'profiler' => [
        'only_exceptions' => false,
    ],
]);

$container->loadFromExtension('web_profiler', [
    'toolbar'             => true,
    'intercept_redirects' => false,
]);

$container->loadFromExtension('monolog', [
    'channels' => [
        'mautic',
        'chrome',
    ],
    'handlers' => [
        'main' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                '!mautic',
            ],
            'max_files' => 7,
        ],
        'console' => [
            'type'   => 'console',
            'bubble' => false,
        ],
        'mautic' => [
            'formatter' => 'mautic.monolog.fulltrace.formatter',
            'type'      => 'rotating_file',
            'path'      => '%kernel.logs_dir%/mautic_%kernel.environment%.php',
            'level'     => 'debug',
            'channels'  => [
                'mautic',
            ],
            'max_files' => 7,
        ],
        'chrome' => [
            'type'     => 'chromephp',
            'level'    => 'debug',
            'channels' => [
                'chrome',
            ],
        ],
    ],
]);

$container->loadFromExtension('maker', [
    'root_namespace' => 'Mautic',
]);

// Allow overriding config without a requiring a full bundle or hacks
if (file_exists($configBaseDir.'/config/config_override.php')) {
    $loader->import($configBaseDir.'/config/config_override.php');
}

// Allow local settings without committing to git such as swift mailer delivery address overrides
if (file_exists($configBaseDir.'/config/config_local.php')) {
    $loader->import($configBaseDir.'/config/config_local.php');
}
