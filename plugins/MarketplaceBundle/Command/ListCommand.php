<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Command;

use MauticPlugin\MarketplaceBundle\Service\PluginCollector;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class ListCommand extends ContainerAwareCommand
{
    private $pluginCollector;

    public function __construct(PluginCollector $pluginCollector)
    {
        parent::__construct();
        $this->pluginCollector = $pluginCollector;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:marketplace:list');
        $this->setDescription('Lists plugins that are available at Packagist.org');
        $this->addOption('page', 'p', InputOption::VALUE_OPTIONAL, 'Page number', 1);
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Packages per page', 15);
        $this->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter the packages', '');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io        = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('command');

        $table = new Table($output);
        $table->setHeaders(['name', 'description', 'downloads', 'favers']);

        $plugins = $this->pluginCollector->collectPackages($input->getOption('page'), $input->getOption('limit'), $input->getOption('filter'));

        foreach ($plugins as $plugin) {
            $color = 'white';
            $table->addRow([
                "<fg={$color}>{$plugin->getName()}</>",
                "<fg={$color}>{$plugin->getDescription()}</>",
                "<fg={$color}>{$plugin->getDownloads()}</>",
                "<fg={$color}>{$plugin->getFavers()}</>",
            ]);
        }

        $table->render();

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Total packages: {$this->pluginCollector->getTotal()}</>");
        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return 0;
    }
}
