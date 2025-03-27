<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

final class CacheProviderTagAware extends AbstractCacheProvider implements CacheProviderTagAwareInterface
{
    public function getCacheAdapter(): TagAwareAdapterInterface
    {
        $adapter = $this->cacheAdapterFactory('cache_adapter_tag_aware');
        \assert($adapter instanceof TagAwareAdapterInterface);

        return $adapter;
    }

    public function invalidateTags(array $tags): bool
    {
        return $this->getCacheAdapter()->invalidateTags($tags);
    }
}
