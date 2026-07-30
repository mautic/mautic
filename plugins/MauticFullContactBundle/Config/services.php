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
        'Services',
    ];

    $services->load('MauticPlugin\\MauticFullContactBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->alias('mautic.plugin.fullcontact.lookup_helper', MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class);
    $services->alias('mautic.integration.fullcontact', MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration::class);
    $services->alias('mautic.integration.fullcontact.config', MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport::class);
};
