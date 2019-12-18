<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Command;

use MauticPlugin\MarketplaceBundle\Api\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

class ListCommand extends ContainerAwareCommand
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:marketplace:list');
        $this->setDescription('Lists plugins that are available at Packagist.org');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('command');

        $table = new Table($output);
        $table->setHeaders(['name', 'description', 'downloads', 'favers']);

        $plugins = $this->connection->getPlugins();

        foreach ($plugins['results'] as $plugin) {
            $color = 'white';
            $table->addRow([
                "<fg={$color}>{$plugin['name']}</>",
                "<fg={$color}>{$plugin['description']}</>",
                "<fg={$color}>{$plugin['downloads']}</>",
                "<fg={$color}>{$plugin['favers']}</>",
            ]);
        }

        $table->render();

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return 0;
    }
}
