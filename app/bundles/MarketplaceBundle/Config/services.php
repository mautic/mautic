<?php

declare(strict_types=1);

use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Mautic\MarketplaceBundle\Service\RouteProvider;
use Mautic\MarketplaceBundle\Service\Config;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
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

    $services->set(MarketplacePermissions::class)->tag('mautic.permissions');

    $services->set(Connection::class)
        ->arg('$httpClient', service('mautic.http.client'));

    $services->set('marketplace.service.plugin_collector', PluginCollector::class);
    $services->alias(PluginCollector::class, 'marketplace.service.plugin_collector');
    $services->set('marketplace.service.route_provider', RouteProvider::class);
    $services->alias(RouteProvider::class, 'marketplace.service.route_provider');
    $services->set('marketplace.service.config', Config::class);
    $services->alias(Config::class, 'marketplace.service.config');
};
