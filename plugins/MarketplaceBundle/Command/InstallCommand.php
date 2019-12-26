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
use Mautic\PluginBundle\Facade\ReloadFacade;
use MauticPlugin\MarketplaceBundle\DTO\Package;
use MauticPlugin\MarketplaceBundle\Service\PluginCollector;
use MauticPlugin\MarketplaceBundle\Service\PluginDownloader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

class InstallCommand extends ContainerAwareCommand
{
    private $pluginCollector;
    private $pluginDownloader;
    private $reloadFacade;

    public function __construct(
        PluginCollector $pluginCollector,
        PluginDownloader $pluginDownloader,
        ReloadFacade $reloadFacade
    ) {
        parent::__construct();
        $this->pluginCollector  = $pluginCollector;
        $this->pluginDownloader = $pluginDownloader;
        $this->reloadFacade     = $reloadFacade;
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
        $stopwatch->start('versions');

        $pluginCollection = $this->pluginCollector->collectPackageVersions($input->getArgument('package'));

        $io->writeln("{$pluginCollection->count()} versions of the plugin metadata fetched in {$stopwatch->stop('versions')->getDuration()} ms");

        $package = $pluginCollection->findLatestVersionPackage('@todo the mautic version here', Package::STABILITY_STABLE);

        $io->writeln("{$package->getVersion()} version is considered to be latest stable");
        $stopwatch->start('download');

        $this->pluginDownloader->download($package);

        $io->writeln("Package distribution downloaded in {$stopwatch->stop('download')->getDuration()} ms");

        $composerApp = new Application();

        $arguments = [
            'command'               => 'install',
            '--no-dev'              => true,
            '--optimize-autoloader' => true,
            '--prefer-dist'         => true,
            '-d'                    => $this->pluginDownloader->getPluginDirectory().$package->getInstallDirName(),
        ];

        $composerApp->setAutoExit(false);

        try {
            $returnCode = $composerApp->run(new ArrayInput($arguments), $output);
        } catch (\Throwable $e) {
            $io->writeln("<fg=red>Composer error: {$e->getMessage()}</>");
        }

        $stopwatch->start('reload');

        $this->reloadFacade->reloadPlugins();

        $io->writeln("Plugin schema installed in {$stopwatch->stop('reload')->getDuration()} ms");

        $event = $stopwatch->stop('total');

        $io->writeln("<fg=green>Total execution time: {$event->getDuration()} ms</>");

        return $returnCode;
    }
}
