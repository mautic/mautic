<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Simple\Psr6Cache;

interface CacheProviderInterface extends TagAwareAdapterInterface
{
    /**
     * @return Psr6Cache
     */
    public function getSimpleCache();
}
