<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to generate production assets.
 */
class GenerateProductionAssetsCommand extends \Symfony\Component\Console\Command\Command
{
    private \Mautic\CoreBundle\Helper\AssetGenerationHelper $assetGenerationHelper;
    private \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper;
    private \Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator;
    private \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper;

    public function __construct(\Mautic\CoreBundle\Helper\AssetGenerationHelper $assetGenerationHelper, \Mautic\CoreBundle\Helper\PathsHelper $pathsHelper, \Symfony\Component\Translation\DataCollectorTranslator $dataCollectorTranslator, \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper)
    {
        $this->assetGenerationHelper = $assetGenerationHelper;
        parent::__construct();
        $this->pathsHelper             = $pathsHelper;
        $this->dataCollectorTranslator = $dataCollectorTranslator;
        $this->coreParametersHelper    = $coreParametersHelper;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container   = $this->getContainer();
        $assetHelper = $this->assetGenerationHelper;

        $pathsHelper = $this->pathsHelper;

        // Combine and minify bundle assets
        $assetHelper->getAssets(true);

        // Minify Mautic Form SDK
        file_put_contents(
            $pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js',
            \Minify::combine([$pathsHelper->getSystemPath('assets', true).'/js/mautic-form-src.js'])
        );
        // Fix the MauticSDK loader
        file_put_contents(
            $pathsHelper->getSystemPath('assets', true).'/js/mautic-form.js',
            str_replace("'mautic-form-src.js'", "'mautic-form.js'",
                file_get_contents($pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js'))
        );
        // Remove temp file.
        unlink($pathsHelper->getSystemPath('assets', true).'/js/mautic-form-tmp.js');

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->dataCollectorTranslator;
        $translator->setLocale($this->coreParametersHelper->get('locale'));

        // Update successful
        $output->writeln('<info>'.$translator->trans('mautic.core.command.asset_generate_success').'</info>');

        return 0;
    }
}
