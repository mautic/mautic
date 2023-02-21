<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache;

interface CacheProviderInterface extends TagAwareAdapterInterface
{
    /**
     * @return Psr16Cache
     */
    public function getSimpleCache();
}
