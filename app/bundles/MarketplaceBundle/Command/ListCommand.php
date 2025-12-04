<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\MarketplaceBundle\DTO\PackageBase;
use Mautic\MarketplaceBundle\Service\PluginCollector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: ListCommand::NAME,
    description: 'Lists plugins that are available at Packagist.org'
)]
class ListCommand
{
    public const NAME = 'mautic:marketplace:list';

    public function __construct(
        private PluginCollector $pluginCollector,
    ) {
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Option(name: 'page', shortcut: 'p', mode: InputOption::VALUE_OPTIONAL, description: 'Page number')]
        int $page = 1, #[\Symfony\Component\Console\Attribute\Option(name: 'limit', shortcut: 'l', mode: InputOption::VALUE_OPTIONAL, description: 'Packages per page')]
        int $limit = 15, #[\Symfony\Component\Console\Attribute\Option(name: 'filter', shortcut: 'f', mode: InputOption::VALUE_OPTIONAL, description: 'Filter the packages')]
        string $filter = '', OutputInterface $output, \Symfony\Component\Console\Style\SymfonyStyle $io): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('command');

        $table = new Table($output);
        $table->setHeaders(['name', 'downloads', 'favers']);

        $plugins = $this->pluginCollector->collectPackages($page, $limit, $filter);

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

        return Command::SUCCESS;
    }
}
