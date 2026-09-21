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

    $services->load('MauticPlugin\\MauticCloudStorageBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set('mautic.integration.amazons3', MauticPlugin\MauticCloudStorageBundle\Integration\AmazonS3Integration::class);
    $services->alias(MauticPlugin\MauticCloudStorageBundle\Integration\AmazonS3Integration::class, 'mautic.integration.amazons3');
};
