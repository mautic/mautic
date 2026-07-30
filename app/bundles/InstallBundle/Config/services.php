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

    $services->alias(Mautic\CoreBundle\Doctrine\Loader\FixturesLoaderInterface::class, Mautic\CoreBundle\Doctrine\Loader\MauticFixturesLoader::class);

    $services->load('Mautic\\InstallBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set(Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias('mautic.install.fixture.lead_field', Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData::class);
    $services->set(Mautic\InstallBundle\InstallFixtures\ORM\RoleData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->set(Mautic\InstallBundle\InstallFixtures\ORM\LoadReportData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->set(Mautic\InstallBundle\Configurator\Step\CheckStep::class)
        ->arg('$projectDir', param('kernel.project_dir'))
        ->tag('mautic.configurator.step', ['priority' => 0]);
    $services->set(Mautic\InstallBundle\Configurator\Step\DoctrineStep::class)->tag('mautic.configurator.step', ['priority' => 1]);
    $services->set(Mautic\InstallBundle\Configurator\Step\UserStep::class)->tag('mautic.configurator.step', ['priority' => 2]);
    $services->set('mautic.install.service', Mautic\InstallBundle\Install\InstallService::class);
};
