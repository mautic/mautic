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
    ];

    $services->load('MauticPlugin\\MauticAnalyticsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    // uncomment If you have any repositories
    // $services->load('MauticPlugin\\MauticAnalyticsBundle\\Entity\\', '../Entity/*Repository.php');

    // Basic definitions with name, display name and icon
    $services->alias('mautic.integration.mauticanalyticsbundle', MauticPlugin\MauticAnalyticsBundle\Integration\MauticAnalyticsBundleIntegration::class);
    // Provides the form types to use for the configuration UI
    $services->alias('mauticanalyticsbundle.integration.configuration', MauticPlugin\MauticAnalyticsBundle\Integration\Support\ConfigSupport::class);
};
