<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('string $projectDir', '%kernel.project_dir%')
        ->public();

    $excludes = [
        'node_modules',
        'vendor',
    ];

    $services->load('MauticPlugin\\GrapesJsBuilderBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\GrapesJsBuilderBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set('grapesjsbuilder.config', MauticPlugin\GrapesJsBuilderBundle\Integration\Config::class);
    $services->alias(MauticPlugin\GrapesJsBuilderBundle\Integration\Config::class, 'grapesjsbuilder.config');
    $services->set('grapesjsbuilder.helper.filemanager', MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager::class);
    $services->alias(MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager::class, 'grapesjsbuilder.helper.filemanager');

    $services->alias('grapesjsbuilder.model', MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel::class);
    // Basic definitions with name, display name and icon
    $services->alias('mautic.integration.grapesjsbuilder', MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration::class);
    // Provides the form types to use for the configuration UI
    $services->alias('grapesjsbuilder.integration.configuration', MauticPlugin\GrapesJsBuilderBundle\Integration\Support\ConfigSupport::class);
    // Tells Mautic what themes it should support when enabled
    $services->alias('grapesjsbuilder.integration.builder', MauticPlugin\GrapesJsBuilderBundle\Integration\Support\BuilderSupport::class);

    $services->get(MauticPlugin\GrapesJsBuilderBundle\InstallFixtures\ORM\GrapesJsData::class)
        ->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
};
