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

    $services->load('Mautic\\CacheBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->alias(Mautic\CacheBundle\Cache\CacheProviderInterface::class, Mautic\CacheBundle\Cache\CacheProvider::class);
    $services->alias('mautic.cache.provider', Mautic\CacheBundle\Cache\CacheProvider::class);
    $services->alias('mautic.cache.provider_tag_aware', Mautic\CacheBundle\Cache\CacheProviderTagAware::class);
    $services->alias('mautic.cache.adapter.redis', Mautic\CacheBundle\Cache\Adapter\RedisAdapter::class);
    $services->alias('mautic.cache.adapter.redis_tag_aware', Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class);

    $services->get(Mautic\CacheBundle\Cache\Adapter\RedisAdapter::class)
        ->tag('mautic.cache.adapter');
    $services->get(Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class)
        ->tag('mautic.cache.adapter');
};
