<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CacheProvider provides a caching mechanism using adapters, it provides both PSR-6 and PSR-16.
 */
abstract class AbstractCacheProvider implements CacheProviderInterface
{
    private ?AdapterInterface $adapter = null;
    private ?Psr16Cache $psr16         = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private ContainerInterface $container,
    ) {
    }

    abstract public function getCacheAdapter(): AdapterInterface;

    public function getSimpleCache(): Psr16Cache
    {
        if (is_null($this->psr16)) {
            $this->psr16 = new Psr16Cache($this->getCacheAdapter());
        }

        return $this->psr16;
    }

    public function getItem($key): CacheItem
    {
        return $this->getCacheAdapter()->getItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->getCacheAdapter()->getItems($keys);
    }

    public function hasItem($key): bool
    {
        return $this->getCacheAdapter()->hasItem($key);
    }

    public function clear(string $prefix = ''): bool
    {
        return $this->getCacheAdapter()->clear();
    }

    public function deleteItem($key): bool
    {
        return $this->getCacheAdapter()->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->getCacheAdapter()->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->getCacheAdapter()->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->getCacheAdapter()->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->getCacheAdapter()->commit();
    }

    protected function cacheAdapterFactory(string $parameter): AdapterInterface
    {
        if (null === $this->adapter) {
            $service       = $this->coreParametersHelper->get($parameter);
            $this->adapter = $this->container->get($service);
        }

        return $this->adapter;
    }
}
