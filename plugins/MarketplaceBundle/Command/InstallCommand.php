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
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;

class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:marketplace:install');
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

        $composerApp = new Application();

        $arguments = [
            'command' => 'require',
            'packages' => ['mautic/mautic-saelos-bundle'],
            '--update-no-dev' => true,
            '--no-suggest' => true,
            '--prefer-dist' => true,
            '-v' => true,
        ];
    
        $returnCode = $composerApp->run(new ArrayInput($arguments), $output);

        dump($returnCode);

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return 0;
    }
}
