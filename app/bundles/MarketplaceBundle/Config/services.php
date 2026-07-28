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
        ->public()
        ->bind('$httpClient', service('mautic.http.client'));

    $excludes = [];

    $services->load('Mautic\\MarketplaceBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');
    $services->set(Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions::class)
        ->arg('$coreParametersHelper', service(Mautic\CoreBundle\Helper\CoreParametersHelper::class));

    $services->set(Mautic\MarketplaceBundle\Api\Connection::class)
        ->arg('$httpClient', service('mautic.http.client'));

    $services->set('marketplace.service.plugin_collector', Mautic\MarketplaceBundle\Service\PluginCollector::class);
    $services->set('marketplace.service.route_provider', Mautic\MarketplaceBundle\Service\RouteProvider::class);
    $services->set('marketplace.service.config', Mautic\MarketplaceBundle\Service\Config::class);
    $services->set('marketplace.service.allowlist', Mautic\MarketplaceBundle\Service\Allowlist::class);

    $services->alias('marketplace.model.package', Mautic\MarketplaceBundle\Model\PackageModel::class);
};
