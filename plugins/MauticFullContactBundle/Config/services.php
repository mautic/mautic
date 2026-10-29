<?php

declare(strict_types=1);

use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
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
    $services->set(LookupHelper::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->arg('$router', service('router'));
    $services->alias('mautic.plugin.fullcontact.lookup_helper', LookupHelper::class);
    $services->alias('mautic.integration.fullcontact', FullContactIntegration::class);
};
