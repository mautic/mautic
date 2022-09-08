<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

interface CacheProviderInterface extends TagAwareAdapterInterface
{
    /**
     * @return \Symfony\Component\Cache\Psr16Cache
     */
    public function getSimpleCache();
}
