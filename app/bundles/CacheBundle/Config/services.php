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

    $services->load('Mautic\\CacheBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');
    $services->set('mautic.cache.clear_cache_subscriber', Mautic\CacheBundle\EventListener\CacheClearSubscriber::class)
        ->arg('$cacheProvider', service('mautic.cache.provider'))
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('kernel.cache_clearer');
    $services->alias(Mautic\CacheBundle\EventListener\CacheClearSubscriber::class, 'mautic.cache.clear_cache_subscriber');

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
