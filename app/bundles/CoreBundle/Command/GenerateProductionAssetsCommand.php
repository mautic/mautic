<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use MatthiasMullie\Minify;
use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to generate production assets.
 */
#[AsCommand(
    name: 'mautic:assets:generate',
    description: 'Combines and minifies asset files into single production files'
)]
class GenerateProductionAssetsCommand extends Command
{
    public function __construct(
        private readonly AssetGenerationHelper $assetGenerationHelper,
        private readonly PathsHelper $pathsHelper,
        private readonly TranslatorInterface $translator,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command builds Symfony Asset Mapper assets, combines and minifies legacy files from node_modules and each bundle's Assets/css/* and Assets/js/* folders into production files stored in root/media/css and root/media/js respectively. It also runs the command elfinder:install internally to install ElFinder assets.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mediaDir  = $this->pathsHelper->getSystemPath('media', true);
        $assetsDir = $this->pathsHelper->getSystemPath('assets', true);
        $vendorDir = $this->pathsHelper->getVendorRootPath();

        $relativeMediaPath = Path::makeRelative($mediaDir, $vendorDir);

        // Check that the directory node_modules exists.
        $nodeModulesDir = $vendorDir.'/node_modules';
        if (!$this->filesystem->exists($nodeModulesDir)) {
            $output->writeln('<error>'.$this->translator->trans("{$nodeModulesDir} does not exist. Execute `npm install` to generate it.").'</error>');

            return Command::FAILURE;
        }

        $ckeditorFile = $mediaDir.'/libraries/ckeditor/ckeditor.js';
        if (!$this->filesystem->exists($ckeditorFile)) {
            $output->writeln('<error>'.$this->translator->trans("{$ckeditorFile} does not exist. Execute `npm install` to generate it.").'</error>');

            return Command::FAILURE;
        }

        foreach ([
            'sass:build'        => [],
            'importmap:install' => ['--no-interaction' => true],
            'asset-map:compile' => [],
        ] as $commandName => $arguments) {
            if (Command::SUCCESS !== $this->runConsoleCommand($commandName, $arguments, $output)) {
                $output->writeln('<error>'.$this->translator->trans("The {$commandName} command failed. Generating production assets was not successful.").'</error>');

                return Command::FAILURE;
            }
        }

        $this->installElFinderAssets($relativeMediaPath);

        // Combine and minify bundle assets
        $this->assetGenerationHelper->getAssets(true);
        $this->ensureStylesheetCompatibilityFiles($mediaDir, $vendorDir);

        $this->moveExtraLibraries($nodeModulesDir, $mediaDir);

        foreach (['mediaelementplayer', 'modal'] as $css_file) {
            $minifier = new Minify\CSS($assetsDir.'/css/'.$css_file.'.css');
            $minifier->minify($mediaDir.'/css/'.$css_file.'.min.css');
        }

        // Minify Mautic Form SDK
        $minifier = new Minify\JS($assetsDir.'/js/mautic-form-src.js');
        $minifier->minify($mediaDir.'/js/mautic-form.js');

        // Fix the MauticSDK loader
        file_put_contents(
            $mediaDir.'/js/mautic-form.js',
            str_replace("'mautic-form-src.js'", "'mautic-form.js'", file_get_contents($mediaDir.'/js/mautic-form.js'))
        );

        // Check that the production assets were correctly generated.
        $productionAssets = [
            'bundles/fmelfinder/css/elfinder.min.css',
            'bundles/fmelfinder/css/theme.css',
            'bundles/fmelfinder/js/elfinder.min.js',
            'css/app.css',
            'css/libraries.css',
            'css/offline.css',
            'js/app.js',
            'js/libraries.js',
            'js/mautic-form.js',
            'js/jquery.min.js',
            'js/froogaloop.min.js',
        ];

        foreach ($productionAssets as $relativePath) {
            $absolutePath = $mediaDir.'/'.$relativePath;
            if (!$this->filesystem->exists($absolutePath)) {
                $output->writeln('<error>The file '.$this->translator->trans("{$absolutePath} does not exist. Generating production assets was not sucessful.").'</error>');

                return Command::FAILURE;
            }
        }

        $output->writeln('<info>'.$this->translator->trans('mautic.core.command.asset_generate_success').'</info>');

        return Command::SUCCESS;
    }

    private function installElFinderAssets(string $mediaDir): void
    {
        $command = $this->getApplication()->find('elfinder:install');

        $command->run(new ArrayInput(['--docroot' => $mediaDir]), new NullOutput());
    }

    /**
     * Asset Mapper loads the main SCSS entry for normal pages. These files keep
     * older entry points, especially the offline page, working during migration.
     */
    private function ensureStylesheetCompatibilityFiles(string $mediaDir, string $rootDir): void
    {
        $cssDir = $mediaDir.'/css';
        $this->filesystem->mkdir($cssDir);

        $appCssFile       = $cssDir.'/app.css';
        $librariesCssFile = $cssDir.'/libraries.css';
        $offlineCssFile   = $cssDir.'/offline.css';
        $sassCssFile      = $rootDir.'/var/sass/app.output.css';

        if (!$this->filesystem->exists($appCssFile)) {
            $this->filesystem->dumpFile($appCssFile, '');
        }

        if (!$this->filesystem->exists($librariesCssFile)) {
            $this->filesystem->dumpFile($librariesCssFile, '');
        }

        if (!$this->filesystem->exists($sassCssFile)) {
            return;
        }

        $this->filesystem->dumpFile(
            $offlineCssFile,
            $this->filesystem->readFile($sassCssFile)."\n".$this->filesystem->readFile($appCssFile)
        );
    }

    /**
     * Run internal asset commands.
     *
     * @param array<string, mixed> $arguments
     */
    private function runConsoleCommand(string $commandName, array $arguments, OutputInterface $output): int
    {
        $command = $this->getApplication()->find($commandName);
        $input   = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }

    /**
     * Following libraries are loaded by public, not administration related features so those cannot be built into one JS file.
     */
    private function moveExtraLibraries(string $nodeModulesDir, string $assetsDir): void
    {
        $this->filesystem->copy("{$nodeModulesDir}/jquery/dist/jquery.min.js", "{$assetsDir}/js/jquery.min.js");
        $this->filesystem->copy("{$nodeModulesDir}/vimeo-froogaloop2/javascript/froogaloop.min.js", "{$assetsDir}/js/froogaloop.min.js");
        $this->copyCarbonPictograms($nodeModulesDir);
    }

    /**
     * Copy all pictogram SVGs from node_modules to the CoreBundle/Assets/pictograms directory.
     */
    private function copyCarbonPictograms(string $nodeModulesDir): void
    {
        $pictogramsSourceDir = "{$nodeModulesDir}/@carbon/pictograms/svg";
        $coreBundleAssetsDir = $this->pathsHelper->getRootPath().'/app/bundles/CoreBundle/Assets/pictograms';

        if (!$this->filesystem->exists($coreBundleAssetsDir)) {
            $this->filesystem->mkdir($coreBundleAssetsDir, 0777);
        }

        if ($this->filesystem->exists($pictogramsSourceDir)) {
            $this->filesystem->mirror($pictogramsSourceDir, $coreBundleAssetsDir, null, ['override' => true, 'delete' => true]);
        }
    }
}
