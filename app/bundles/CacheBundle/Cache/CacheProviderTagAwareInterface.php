<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

interface CacheProviderTagAwareInterface extends CacheProviderInterface, TagAwareAdapterInterface
{
}
