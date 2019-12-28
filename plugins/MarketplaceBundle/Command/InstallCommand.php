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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Facade\ReloadFacade;
use MauticPlugin\MarketplaceBundle\DTO\Package;
use MauticPlugin\MarketplaceBundle\Service\ComposerCombiner;
use MauticPlugin\MarketplaceBundle\Service\PluginCollector;
use MauticPlugin\MarketplaceBundle\Service\PluginDownloader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

class InstallCommand extends ContainerAwareCommand
{
    private $pluginCollector;
    private $pluginDownloader;
    private $reloadFacade;
    private $coreParametersHelper;
    private $filesystem;
    private $composerCombiner;

    public function __construct(
        PluginCollector $pluginCollector,
        PluginDownloader $pluginDownloader,
        ReloadFacade $reloadFacade,
        CoreParametersHelper $coreParametersHelper,
        Filesystem $filesystem,
        ComposerCombiner $composerCombiner
    ) {
        parent::__construct();
        $this->pluginCollector      = $pluginCollector;
        $this->pluginDownloader     = $pluginDownloader;
        $this->reloadFacade         = $reloadFacade;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->filesystem           = $filesystem;
        $this->composerCombiner     = $composerCombiner;
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
        $this->composerCombiner->useComposerCombinedJson();

        $io        = new SymfonyStyle($input, $output);
        $stopwatch = new Stopwatch();
        $stopwatch->start('total');
        $stopwatch->start('composer');

        $composerApp = new Application();

        $arguments = [
            'command'               => 'require',
            'packages'              => [$input->getArgument('package')],
            // '--no-dev'              => true, // @todo set acutal env.
            '--no-scripts'              => true,
            // '--optimize-autoloader' => true,
            // '--no-autoloader' => true,
            '--prefer-dist'         => true,
            // '--profile' => true,
            // '--no-progress' => true,
            // '-d'                    => $this->pluginDownloader->getPluginDirectory().$package->getInstallDirName(),
        ];

        $composerApp->setAutoExit(false);

        try {
            $returnCode = $composerApp->run(new ArrayInput($arguments), $output);
        } catch (\Throwable $e) {
            $io->writeln("<fg=red>Composer error: {$e->getMessage()}</>");
        }

        $io->writeln("Composer dependencies installed in {$stopwatch->stop('composer')->getDuration()} ms");

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
