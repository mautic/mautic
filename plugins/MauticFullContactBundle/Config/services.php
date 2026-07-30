<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
    $services->set(MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->arg('$router', service('router'));
    $services->alias('mautic.plugin.fullcontact.lookup_helper', MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class);
    $services->set(MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration::class);
    $services->alias('mautic.integration.fullcontact', MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration::class);
};
