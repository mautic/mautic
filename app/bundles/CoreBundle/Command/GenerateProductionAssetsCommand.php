<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to generate production assets.
 */
class GenerateProductionAssetsCommand extends Command
{
    private AssetGenerationHelper $assetGenerationHelper;
    private PathsHelper $pathsHelper;
    private TranslatorInterface $translator;

    public function __construct(
        AssetGenerationHelper $assetGenerationHelper,
        PathsHelper $pathsHelper,
        TranslatorInterface $translator
    ) {
        parent::__construct();

        $this->assetGenerationHelper = $assetGenerationHelper;
        $this->pathsHelper           = $pathsHelper;
        $this->translator            = $translator;
    }

    protected function configure()
    {
        $this->setName('mautic:assets:generate')
            ->setDescription('Combines and minifies asset files from each bundle into single production files')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command Combines and minifies files from each bundle's Assets/css/* and Assets/js/* folders into single production files stored in root/media/css and root/media/js respectively.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Combine and minify bundle assets
        $this->assetGenerationHelper->getAssets(true);

        // Minify Mautic Form SDK
        file_put_contents(
            $this->pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js',
            \Minify::combine([$this->pathsHelper->getSystemPath('assets', true).'/js/mautic-form-src.js'])
        );
        // Fix the MauticSDK loader
        file_put_contents(
            $this->pathsHelper->getSystemPath('assets', true).'/js/mautic-form.js',
            str_replace("'mautic-form-src.js'", "'mautic-form.js'",
                file_get_contents($this->pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js'))
        );
        // Remove temp file.
        unlink($this->pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js');

        // Update successful
        $output->writeln('<info>'.$this->translator->trans('mautic.core.command.asset_generate_success').'</info>');

        return 0;
    }
}
