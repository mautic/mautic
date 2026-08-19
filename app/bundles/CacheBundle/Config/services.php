<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('Mautic\\CacheBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');
    $services->set(Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter::class)
        ->arg('$prefix', param('mautic.cache_prefix'))
        ->arg('$lifetime', param('mautic.cache_lifetime'))
        ->arg('$directory', param('mautic.tmp_path'))
        ->tag('mautic.cache.adapter');
    $services->alias('mautic.cache.adapter.filesystem', Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter::class);
    $services->set(Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter::class)
        ->arg('$servers', param('mautic.cache_adapter_memcached'))
        ->arg('$namespace', param('mautic.cache_prefix'))
        ->arg('$lifetime', param('mautic.cache_lifetime'))
        ->tag('mautic.cache.adapter');
    $services->alias('mautic.cache.adapter.memcached', Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter::class);
    $services->set(Mautic\CacheBundle\EventListener\CacheClearSubscriber::class)
        ->arg('$cacheProvider', service(Mautic\CacheBundle\Cache\CacheProvider::class))
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('kernel.cache_clearer');

    $services->alias(Mautic\CacheBundle\Cache\CacheProviderInterface::class, Mautic\CacheBundle\Cache\CacheProvider::class);
    $services->alias('mautic.cache.adapter.redis', Mautic\CacheBundle\Cache\Adapter\RedisAdapter::class);
    $services->alias('mautic.cache.adapter.redis_tag_aware', Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class);

    $services->get(Mautic\CacheBundle\Cache\Adapter\RedisAdapter::class)
        ->tag('mautic.cache.adapter');
    $services->get(Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class)
        ->tag('mautic.cache.adapter');
};
