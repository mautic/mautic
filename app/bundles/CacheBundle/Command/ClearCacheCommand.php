<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Command;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * CLI Command to clear the application cache.
 */
#[AsCommand(
    name: 'mautic:cache:clear',
    description: "Clears Mautic's cache"
)]
class ClearCacheCommand
{
    public function __construct(
        private CacheProviderInterface $cacheProvider,
    ) {
    }

    public function __invoke(): int
    {
        return (int) !$this->cacheProvider->clear();
    }
}
