<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to generate production assets.
 */
class GenerateProductionAssetsCommand extends ContainerAwareCommand
{
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container   = $this->getContainer();
        $assetHelper = $container->get('mautic.helper.assetgeneration');

        $pathsHelper = $container->get('mautic.helper.paths');

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
        $translator = $container->get('translator');
        $translator->setLocale($container->get('mautic.helper.core_parameters')->getParameter('locale'));

        // Update successful
        $output->writeln('<info>'.$translator->trans('mautic.core.command.asset_generate_success').'</info>');

        return 0;
    }
}
