<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use Composer\Console\Application;
use MauticPlugin\MarketplaceBundle\Service\ComposerCombiner;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class PackageRemover
{
    private $composerCombiner;

    public function __construct(ComposerCombiner $composerCombiner)
    {
        $this->composerCombiner = $composerCombiner;
    }

    public function remove(string $packageName, OutputInterface $output, array $arguments = []): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('composer');

        $output->writeln("Package {$packageName} is about to be removed");

        $this->composerCombiner->useComposerCombinedJson();

        $composerApp     = new Application();
        $defautArguments = [
            'command'               => 'remove',
            'packages'              => [$packageName],
            // '--no-dev'              => true, // @todo set by env var.
            '--no-scripts'          => true,
            '--optimize-autoloader' => true,
        ];
        dump($arguments + $defautArguments);
        $composerApp->setAutoExit(false);

        $returnCode = $composerApp->run(new ArrayInput($arguments + $defautArguments), $output);

        $output->writeln("Composer package {$packageName} removed in {$stopwatch->stop('composer')->getDuration()} ms");

        return $returnCode;
    }
}
