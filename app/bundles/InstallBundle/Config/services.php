<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Helper/SchemaHelper.php',
    ];

    $services->set(Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class);
    $services->alias(Mautic\CoreBundle\Doctrine\Loader\FixturesLoaderInterface::class, Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class);

    $services->load('Mautic\\InstallBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set('mautic.install.configurator.step.doctrine', Mautic\InstallBundle\Configurator\Step\DoctrineStep::class)->tag('mautic.configurator.step', ['priority' => 1]);
    $services->set('mautic.install.configurator.step.user', Mautic\InstallBundle\Configurator\Step\UserStep::class)->tag('mautic.configurator.step', ['priority' => 2]);
    $services->set('mautic.install.service', Mautic\InstallBundle\Install\InstallService::class);
};
