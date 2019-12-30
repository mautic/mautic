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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Facade\ReloadFacade;
use MauticPlugin\MarketplaceBundle\Service\PackageInstaller;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

class InstallCommand extends ContainerAwareCommand
{
    private $packageInstaller;
    private $reloadFacade;
    private $coreParametersHelper;
    private $filesystem;

    public function __construct(
        PackageInstaller $packageInstaller,
        ReloadFacade $reloadFacade,
        CoreParametersHelper $coreParametersHelper,
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->packageInstaller     = $packageInstaller;
        $this->reloadFacade         = $reloadFacade;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->filesystem           = $filesystem;
    }

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
        $stopwatch->start('total');

        try {
            $returnCode = $this->packageInstaller->install($input->getArgument('package'), $output);
        } catch (\Throwable $e) {
            $io->writeln("<fg=red>Composer error: {$e->getMessage()}</>");
        }

        $stopwatch->start('cache');

        $this->filesystem->remove($this->coreParametersHelper->getParameter('kernel.cache_dir'));

        $io->writeln("Mautic cache cleared in {$stopwatch->stop('cache')->getDuration()} ms");
        $stopwatch->start('reload');

        $this->reloadFacade->reloadPlugins();

        $io->writeln("Plugin schema installed in {$stopwatch->stop('reload')->getDuration()} ms");

        $event = $stopwatch->stop('total');

        $io->writeln("<fg=green>Total execution time: {$event->getDuration()} ms</>");

        return $returnCode;
    }
}
