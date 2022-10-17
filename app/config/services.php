<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

// This is loaded by \Mautic\CoreBundle\DependencyInjection\MauticCoreExtension to auto-wire services
// if the bundle do not cover it itself by their own *Extension and services.php which is prefered.
return function (ContainerConfigurator $configurator, ContainerInterface $container) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure() // Automatically registers services as commands as was in M3
        ->public() // Set as public as was the default in M3
    ;

    // These are excludes only for the CoreBundle. Do not add exclues from other bundles here.
    // Each bundle can exclude their own folders or files.
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
        'Session/Storage/Handler/RedisSentinelSessionHandler.php',
        'Templating/Engine/PhpEngine.php', // Will be removed in M5
        'Templating/Helper/FormHelper.php',
        'Templating/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
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
