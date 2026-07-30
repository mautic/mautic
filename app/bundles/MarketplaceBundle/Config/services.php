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

    $excludes = [];

    $services->load('Mautic\\MarketplaceBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set('marketplace.permissions', Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions::class)->tag('mautic.permissions');

    $services->set(Mautic\MarketplaceBundle\Api\Connection::class)
        ->arg('$httpClient', \Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.http.client'));

    $services->set('marketplace.service.plugin_collector', Mautic\MarketplaceBundle\Service\PluginCollector::class);
    $services->alias(Mautic\MarketplaceBundle\Service\PluginCollector::class, 'marketplace.service.plugin_collector');
    $services->set('marketplace.service.route_provider', Mautic\MarketplaceBundle\Service\RouteProvider::class);
    $services->alias(Mautic\MarketplaceBundle\Service\RouteProvider::class, 'marketplace.service.route_provider');
    $services->set('marketplace.service.config', Mautic\MarketplaceBundle\Service\Config::class);
    $services->alias(Mautic\MarketplaceBundle\Service\Config::class, 'marketplace.service.config');

    $services->set(Mautic\MarketplaceBundle\Service\Allowlist::class)
        ->arg('$httpClient', \Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.http.client'));
};
