<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

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
    $services->set('mautic.install.configurator.step.check', Mautic\InstallBundle\Configurator\Step\CheckStep::class)
        ->arg('$projectDir', param('kernel.project_dir'))
        ->tag('mautic.configurator.step', ['priority' => 0]);
    $services->alias(Mautic\InstallBundle\Configurator\Step\CheckStep::class, 'mautic.install.configurator.step.check');
    $services->set('mautic.install.configurator.step.doctrine', Mautic\InstallBundle\Configurator\Step\DoctrineStep::class)->tag('mautic.configurator.step', ['priority' => 1]);
    $services->set('mautic.install.configurator.step.user', Mautic\InstallBundle\Configurator\Step\UserStep::class)->tag('mautic.configurator.step', ['priority' => 2]);
    $services->set('mautic.install.service', Mautic\InstallBundle\Install\InstallService::class);
};
