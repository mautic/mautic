<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\MarketplaceBundle\DTO\PackageBase;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class ListCommand extends Command
{
    public const NAME = 'mautic:marketplace:list';

    private PluginCollector $pluginCollector;

    public function __construct(PluginCollector $pluginCollector)
    {
        parent::__construct();
        $this->pluginCollector = $pluginCollector;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
        $this->setDescription('Lists plugins that are available at Packagist.org');
        $this->addOption('page', 'p', InputOption::VALUE_OPTIONAL, 'Page number', 1);
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Packages per page', 15);
        $this->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Filter the packages', '');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io        = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('command');

        $table = new Table($output);
        $table->setHeaders(['name', 'downloads', 'favers']);

        $plugins = $this->pluginCollector->collectPackages($input->getOption('page'), $input->getOption('limit'), $input->getOption('filter'));

        /** @var PackageBase $plugin */
        foreach ($plugins as $plugin) {
            $color       = 'white';
            $delimiter   = "\n    ";
            $description = $plugin->description ? $delimiter.wordwrap($plugin->description, 50, $delimiter) : '';
            $table->addRow([
                "<fg={$color}>{$plugin->name}{$description}</>",
                "<fg={$color}>{$plugin->downloads}</>",
                "<fg={$color}>{$plugin->favers}</>",
            ]);
        }

        $table->render();

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Total packages: {$this->pluginCollector->getTotal()}</>");
        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return 0;
    }
}
