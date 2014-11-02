<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\UpdateHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * CLI Command to update the application
 */
class ApplyUpdatesCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:update:apply');
        $this->addOption('--force', null, InputOption::VALUE_OPTIONAL);
        $this->setHelp(<<<EOT
The <info>%command.name%</info> command updates the Mautic application.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force=true</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $force   = (!empty($options['force'])) ? true : false;

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale('en_US');

        if (!$force) {
            $dialog  = $this->getHelperSet()->get('dialog');
            $confirm = $dialog->select(
                $output,
                $translator->trans('mautic.core.update.confirm_application_update'),
                array(
                    $translator->trans('mautic.core.form.no'),
                    $translator->trans('mautic.core.form.yes'),
                ),
                0
            );

            if (!$confirm) {
                return 0;
            }
        }

        $cacheFile = $this->getContainer()->getParameter('kernel.root_dir') . '/cache/lastUpdateCheck.txt';

        // First things first, check to make sure cached update data exists as we pull the version info from it, otherwise run the fetch routine
        if (!is_readable($cacheFile)) {
            $command = $this->getApplication()->find('mautic:update:find');
            $input = new ArrayInput(array(
                'command' => 'mautic:update:find',
                '--env'   => (!empty($options['env'])) ? $options['env'] : 'dev'
            ));
            $command->run($input, $output);

            // If the cache file still doesn't exist, there's nothing else we can do
            if (!is_readable($cacheFile)) {
                $output->writeln('<error>' . $translator->trans('mautic.core.update.no_cache_data') . '</error>');

                return 1;
            }
        }

        $update = (array) json_decode(file_get_contents($cacheFile));

        $updateHelper = new UpdateHelper($this->getContainer()->get('mautic.factory'));

        // Fetch the update package
        $package = $updateHelper->fetchPackage($this->getContainer()->getParameter('kernel.root_dir'), $update['version']);

        if ($package['error']) {
            $output->writeln('<error>' . $translator->trans($package['message']) . '</error>');

            return 1;
        }

        // TODO - Change the name reference to use the real names when we have it
        $zipFile = $this->getContainer()->getParameter('kernel.root_dir') . '/cache/mautic-head.zip';

        $zipper = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if ($archive !== true) {
            $zipper->close();
            $output->writeln('<error>' . $translator->trans('mautic.core.update.could_not_open_archive') . '</error>');

            return 1;
        }

        // Extract the archive file now in place
        $zipper->extractTo(dirname($this->getContainer()->getParameter('kernel.root_dir')));

        // TODO - Updates will include a list of deleted files, process those

        // TODO - When we have updated the packaging script to include compiled JS files, remove this step
        @unlink(dirname($this->getContainer()->getParameter('kernel.root_dir') . '/media/js/app.js'));
        @unlink(dirname($this->getContainer()->getParameter('kernel.root_dir') . '/media/js/libraries.js'));

        // Migrate the database to the current version
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $input = new ArrayInput(array(
            'command'          => 'doctrine:migrations:migrate',
            '--env'            => (!empty($options['env'])) ? $options['env'] : 'dev',
            '--no-interaction' => true
        ));
        $exitCode = $command->run($input, $output);

        if ($exitCode !== 0) {
            $output->writeln('<error>' . $translator->trans('mautic.core.update.error_performing_migration') . '</error>');
        }

        // Clear the cached update data and the download package now that we've updated
        @unlink($cacheFile);
        @unlink($zipFile);

        // Update successful
        $output->writeln('<info>' . $translator->trans('mautic.core.update.update_successful', array('%version%' => $update['version'])) . '</info>');

        return 0;
    }
}
