<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\Command;

use Mautic\CacheBundle\Cache\CacheProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to clear the application cache.
 */
class ClearCacheCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this->setName('mautic:cache:clear')
            ->setDescription('Clears Mautic\'s cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var CacheProvider $cacheProvider */
        $cacheProvider = $this->getContainer()->get('mautic.cache.provider');

        return (int) !$cacheProvider->clear();
    }
}
