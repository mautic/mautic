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
    $services->set('marketplace.permissions', Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions::class);
    $services->set('marketplace.api.connection', Mautic\MarketplaceBundle\Api\Connection::class);
    $services->set('marketplace.service.plugin_collector', Mautic\MarketplaceBundle\Service\PluginCollector::class);
    $services->set('marketplace.service.route_provider', Mautic\MarketplaceBundle\Service\RouteProvider::class);
    $services->set('marketplace.service.config', Mautic\MarketplaceBundle\Service\Config::class);
    $services->set('marketplace.service.allowlist', Mautic\MarketplaceBundle\Service\Allowlist::class);

    $services->alias('marketplace.model.package', Mautic\MarketplaceBundle\Model\PackageModel::class);
};
