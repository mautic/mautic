<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Command;

use Composer\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class InstallCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:marketplace:install');
        $this->setDescription('Lists plugins that are available at Packagist.org');
        $this->addArgument(
            'package',
            InputOption::VALUE_REQUIRED,
            'Provide package name in format vendor_name/package_name.'
        );
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

        $composerApp = new Application();

        $arguments = [
            'command'  => 'require',
            'packages' => [$input->getArgument('package')],
            // '--update-no-dev' => true, // @todo read the value from env.
            '--no-suggest'  => true,
            '--no-scripts'  => true,
            '--prefer-dist' => true,
            '-v'            => true, // also, enable in dev only.
        ];

        $composerApp->setAutoExit(false);

        try {
            $returnCode = $composerApp->run(new ArrayInput($arguments), $output);
        } catch (\Throwable $e) {
            $io->writeln("<fg=red>Composer error: {$e->getMessage()}</>");
        }

        dump($returnCode);

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return $returnCode;
    }
}
