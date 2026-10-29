<?php

declare(strict_types=1);

use Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter;
use Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter;
use Mautic\CacheBundle\EventListener\CacheClearSubscriber;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CacheBundle\Cache\Adapter\RedisAdapter;
use Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter;
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
    $services->set(FilesystemTagAwareAdapter::class)
        ->arg('$prefix', param('mautic.cache_prefix'))
        ->arg('$lifetime', param('mautic.cache_lifetime'))
        ->arg('$directory', param('mautic.tmp_path'))
        ->tag('mautic.cache.adapter');
    $services->set(MemcachedTagAwareAdapter::class)
        ->arg('$servers', param('mautic.cache_adapter_memcached'))
        ->arg('$namespace', param('mautic.cache_prefix'))
        ->arg('$lifetime', param('mautic.cache_lifetime'))
        ->tag('mautic.cache.adapter');

    $services->set(CacheClearSubscriber::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('kernel.cache_clearer');

    $services->alias(CacheProviderInterface::class, CacheProvider::class);

    $services->get(RedisAdapter::class)
        ->tag('mautic.cache.adapter');
    $services->get(RedisTagAwareAdapter::class)
        ->tag('mautic.cache.adapter');
};
