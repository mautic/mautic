<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\UpdateHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
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
        $this->setName('mautic:update:apply')
            ->setDescription('Updates the Mautic application')
            ->setDefinition(array(
                new InputOption(
                    'force', null, InputOption::VALUE_NONE,
                    'Bypasses the verification check.'
                )
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command updates the Mautic application.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();
        $force   = $options['force'];

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

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
                '--env'   => $options['env']
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
        $package = $updateHelper->fetchPackage($this->getContainer()->getParameter('kernel.root_dir'), $update['package']);

        if ($package['error']) {
            $output->writeln('<error>' . $translator->trans($package['message']) . '</error>');

            return 1;
        }

        $zipFile = $this->getContainer()->getParameter('kernel.root_dir') . '/cache/' . basename($update['package']);

        $zipper = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if ($archive !== true) {
            $zipper->close();
            $output->writeln('<error>' . $translator->trans('mautic.core.update.could_not_open_archive') . '</error>');

            return 1;
        }

        // Extract the archive file now in place
        $zipper->extractTo(dirname($this->getContainer()->getParameter('kernel.root_dir')));

        // Clear the dev and prod cache instances to reset the system
        $command = $this->getApplication()->find('cache:clear');
        $input = new ArrayInput(array(
            'command'          => 'cache:clear',
            '--env'            => 'prod'
        ));
        $command->run($input, $output);
        $input = new ArrayInput(array(
            'command'          => 'cache:clear',
            '--env'            => 'dev'
        ));
        $command->run($input, $output);

        // TODO - Updates will include a list of deleted files, process those

        // Migrate the database to the current version
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $input = new ArrayInput(array(
            'command'          => 'doctrine:migrations:migrate',
            '--env'            => $options['env'],
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
