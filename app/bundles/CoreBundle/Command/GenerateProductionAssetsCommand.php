<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to generate production assets.
 */
class GenerateProductionAssetsCommand extends Command
{
    public function __construct(
        private AssetGenerationHelper $assetGenerationHelper,
        private PathsHelper $pathsHelper,
        private TranslatorInterface $translator,
        private Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('mautic:assets:generate')
            ->setDescription('Combines and minifies asset files into single production files')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command Combines and minifies files from node_modules and each bundle's Assets/css/* and Assets/js/* folders into single production files stored in root/media/css and root/media/js respectively. It allso runs the command elfinder:install internally to install ElFinder assets.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->installElFinderAssets();

        // Combine and minify bundle assets
        $this->assetGenerationHelper->getAssets(true);

        $assetsDir = $this->pathsHelper->getAssetsPath();

        // Minify Mautic Form SDK
        file_put_contents(
            $assetsDir.'/js/mautic-form-tmp.js',
            (new \Minify(new \Minify_Cache_Null()))->combine([$assetsDir.'/js/mautic-form-src.js'])
        );
        // Fix the MauticSDK loader
        file_put_contents(
            $assetsDir.'/js/mautic-form.js',
            str_replace("'mautic-form-src.js'", "'mautic-form.js'",
                file_get_contents($assetsDir.'/js/mautic-form-tmp.js'))
        );
        // Remove temp file.
        unlink($assetsDir.'/js/mautic-form-tmp.js');

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
        ];

        foreach ($productionAssets as $relativePath) {
            $absolutePath = $assetsDir.'/'.$relativePath;
            if (!$this->filesystem->exists($absolutePath)) {
                $output->writeln('<error>The file '.$this->translator->trans("{$absolutePath} does not exist. Generating production assets was not sucessful.").'</error>');

                return Command::FAILURE;
            }
        }

        $output->writeln('<info>'.$this->translator->trans('mautic.core.command.asset_generate_success').'</info>');

        return Command::SUCCESS;
    }

    private function installElFinderAssets(): void
    {
        $command = $this->getApplication()->find('elfinder:install');

        $command->run(new ArrayInput(['--docroot' => 'media']), new NullOutput());
    }

    protected static $defaultDescription = 'Combines and minifies asset files into single production files';
}
