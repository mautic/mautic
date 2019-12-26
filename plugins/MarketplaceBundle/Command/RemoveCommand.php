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

use Composer\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class RemoveCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('mautic:marketplace:remove');
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
            'command'  => 'remove',
            'packages' => [$input->getArgument('package')],
            // '--update-no-dev' => true, // set value by current env.
            '-v' => true,
        ];

        $composerApp->setAutoExit(false);

        $returnCode = $composerApp->run(new ArrayInput($arguments), $output);

        dump($returnCode);

        $event = $stopwatch->stop('command');

        $io->writeln("<fg=green>Execution time: {$event->getDuration()} ms</>");

        return 0;
    }
}
