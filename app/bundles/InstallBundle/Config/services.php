<?php

declare(strict_types=1);

use Mautic\CoreBundle\Doctrine\Loader\FixturesLoaderInterface;
use Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Configurator\Step\UserStep;
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

    $services->alias(FixturesLoaderInterface::class, MauticFixturesLoader::class);

    $services->load('Mautic\\InstallBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->alias('mautic.install.fixture.lead_field', LeadFieldData::class);
    $services->set(CheckStep::class)
        ->arg('$projectDir', param('kernel.project_dir'))
        ->tag('mautic.configurator.step', ['priority' => 0]);
    $services->set(DoctrineStep::class)->tag('mautic.configurator.step', ['priority' => 1]);
    $services->set(UserStep::class)->tag('mautic.configurator.step', ['priority' => 2]);
};
