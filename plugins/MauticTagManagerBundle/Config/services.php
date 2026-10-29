<?php

declare(strict_types=1);

use MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration;
use MauticPlugin\MauticTagManagerBundle\Integration\Support\ConfigSupport;
use MauticPlugin\MauticTagManagerBundle\Security\Permissions\TagManagerPermissions;
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

    $services->load('MauticPlugin\\MauticTagManagerBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('MauticPlugin\\MauticTagManagerBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias('mautic.integration.tagmanager', TagManagerIntegration::class);
    $services->alias('mautic.integration.tagmanager.config', ConfigSupport::class);
    $services->set(TagManagerPermissions::class);
};
