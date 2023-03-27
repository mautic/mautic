<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

return function (ContainerConfigurator $configurator, ContainerInterface $container) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $bundles = array_merge(
        $container->getParameter('mautic.bundles'),
        $container->getParameter('mautic.plugin.bundles')
    );

    foreach ($bundles as $bundle) {
        $bundleDirectory = $bundle['directory'];
        $bundleNamespace = $bundle['namespace'];

        if (file_exists("$bundleDirectory/Config/services.php")) {
            continue;
        }

        $services->load("$bundleNamespace\\", $bundleDirectory)
            ->exclude("$bundleDirectory/{".implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

        $entityDirectory = "$bundleDirectory/Entity";

        if (is_dir($entityDirectory)) {
            $services->load("$bundleNamespace\\Entity\\", "$entityDirectory/*Repository.php");
        }
    }
};
