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
        private AssetGenerationHelper $assetGenerationHelper,
        private PathsHelper $pathsHelper,
        private TranslatorInterface $translator,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command Combines and minifies files from node_modules and each bundle's Assets/css/* and Assets/js/* folders into single production files stored in root/media/css and root/media/js respectively. It allso runs the command elfinder:install internally to install ElFinder assets.

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

        $this->installElFinderAssets($relativeMediaPath);

        // Combine and minify bundle assets
        $this->assetGenerationHelper->getAssets(true);

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
