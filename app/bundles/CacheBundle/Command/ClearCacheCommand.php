<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Command;

use Mautic\CacheBundle\Cache\CacheProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to clear the application cache.
 */
class ClearCacheCommand extends \Symfony\Component\Console\Command\Command
{
    private \Mautic\CacheBundle\Cache\CacheProvider $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('mautic:cache:clear')
            ->setDescription('Clears Mautic\'s cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var CacheProvider $cacheProvider */
        $cacheProvider = $this->cacheProvider;

        return (int) !$cacheProvider->clear();
    }
}
