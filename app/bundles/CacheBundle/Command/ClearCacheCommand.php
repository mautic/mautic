<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Command;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to clear the application cache.
 */
class ClearCacheCommand extends Command
{
    private CacheProviderInterface $cacheProvider;

    public function __construct(CacheProviderInterface $cacheProvider)
    {
        parent::__construct();

        $this->cacheProvider = $cacheProvider;
    }

    protected function configure(): void
    {
        $this->setName('mautic:cache:clear')
            ->setDescription('Clears Mautic\'s cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return (int) !$this->cacheProvider->clear();
    }
}
