<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheProvider extends AbstractCacheProvider
{
    public function getCacheAdapter(): AdapterInterface
    {
        return $this->cacheAdapterFactory('cache_adapter');
    }
}
