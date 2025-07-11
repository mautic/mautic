<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Command;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to clear the application cache.
 */
#[AsCommand(
    name: 'mautic:cache:clear',
    description: "Clears Mautic's cache"
)]
class ClearCacheCommand extends Command
{
    public function __construct(
        private CacheProviderInterface $cacheProvider,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return (int) !$this->cacheProvider->clear();
    }
}
