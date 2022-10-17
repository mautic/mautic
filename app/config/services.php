<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

// This is loaded by \Mautic\CoreBundle\DependencyInjection\MauticCoreExtension to auto-wire Commands
// as they were done in M3 which must be done when the bundle config.php's services are processed to prevent
// Symfony attempting to auto-wire commands manually registered by bundle
return function (ContainerConfigurator $configurator, ContainerInterface $container) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure() // Automatically registers services as commands as was in M3
        ->public() // Set as public as was the default in M3
    ;

    $excludes = [
        'Doctrine',
        'Model/IteratorExportDataModel.php',
        'Form/EventListener/FormExitSubscriber.php',
        'Release',
        'Helper/Chart',
        'Helper/CommandResponse.php',
        'Helper/Language/Installer.php',
        'Helper/PageHelper.php',
        'Helper/Tree/IntNode.php',
        'Helper/Update/Github/Release.php',
        'Helper/Update/PreUpdateChecks',
        'Helper/RabbitMqProducer.php',
        'Session/Storage/Handler/RedisSentinelSessionHandler.php',
        'Templating/Engine/PhpEngine.php', // Will be removed in M5
        'Templating/Helper/FormHelper.php',
        'Templating/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
        'MauticQueueBundle.php',
        'Aggregate/Collection',
        'Aggregate/Calculator.php',
        'Services',
        'Api',
        'Integration/Salesforce',
    ];

    $bundles = array_merge($container->getParameter('mautic.bundles'), $container->getParameter('mautic.plugin.bundles'));

    // Autoconfigure services for bundles that do not have its own Config/services.php
    foreach ($bundles as $bundle) {
        if (file_exists($bundle['directory'].'/Config/services.php')) {
            continue;
        }

        $services->load($bundle['namespace'].'\\', $bundle['directory'])
            ->exclude($bundle['directory'].'/{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    }
};
