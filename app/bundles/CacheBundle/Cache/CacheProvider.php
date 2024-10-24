<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException as Psr6CacheInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Povides caching mechanism using adapters, it provides both PSR-6 and PSR-16.
 */
final class CacheProvider implements CacheProviderInterface
{
    private ?Psr16Cache $psr16 = null;

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private ContainerInterface $container
    ) {
    }

    public function getCacheAdapter(): TagAwareAdapterInterface|TagAwareCacheInterface
    {
        $selectedAdapter = $this->coreParametersHelper->get('cache_adapter');
        if (!$selectedAdapter || !$this->container->has($selectedAdapter)) {
            throw new InvalidArgumentException('Requested cache adapter "'.$selectedAdapter.'" is not available');
        }

        $adaptor = $this->container->get($selectedAdapter);
        if (!$adaptor instanceof TagAwareAdapterInterface) {
            throw new InvalidArgumentException(sprintf('Requested cache adapter "%s" is not a %s', $selectedAdapter, TagAwareAdapterInterface::class));
        }

        return $adaptor;
    }

    public function getSimpleCache(): Psr16Cache
    {
        if (is_null($this->psr16)) {
            $this->psr16 = new Psr16Cache($this->getCacheAdapter());
        }

        return $this->psr16;
    }

    /**
     * @param string $key
     *
     * @throws Psr6CacheInterface
     */
    public function getItem($key): CacheItem
    {
        return $this->getCacheAdapter()->getItem($key);
    }

    /**
     * @return CacheItem[]|\Traversable
     *
     * @throws Psr6CacheInterface
     */
    public function getItems(array $keys = []): \Traversable
    {
        return $this->getCacheAdapter()->getItems($keys);
    }

    /**
     * @param string $key
     *
     * @throws Psr6CacheInterface
     */
    public function hasItem($key): bool
    {
        return $this->getCacheAdapter()->hasItem($key);
    }

    public function clear(string $prefix = ''): bool
    {
        return $this->getCacheAdapter()->clear($prefix);
    }

    public function deleteItem($key): bool
    {
        return $this->getCacheAdapter()->deleteItem($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys An array of keys that should be removed from the pool
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws Psr6CacheInterface If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *                            MUST be thrown
     */
    public function deleteItems(array $keys): bool
    {
        return $this->getCacheAdapter()->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item The cache item to save
     *
     * @return bool True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->getCacheAdapter()->save($item);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item The cache item to save
     *
     * @return bool False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->getCacheAdapter()->saveDeferred($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit(): bool
    {
        return $this->getCacheAdapter()->commit();
    }

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws Psr6CacheInterface When $tags is not valid
     */
    public function invalidateTags(array $tags): bool
    {
        return $this->getCacheAdapter()->invalidateTags($tags);
    }
}
