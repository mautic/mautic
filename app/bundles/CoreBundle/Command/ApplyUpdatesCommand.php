<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

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
                ),
                new InputOption('update-package', 'p', InputOption::VALUE_OPTIONAL, 'Optional full path to the update package to apply.'),
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command updates the Mautic application.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>

To force install a local package, pass the full path to the package as follows:

<info>php %command.full_name% --update-package=/path/to/updatepackage.zip</info>
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
        $package = $options['update-package'];

        $appRoot = dirname($this->getContainer()->getParameter('kernel.root_dir'));

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        if ($package) {
            if (!file_exists($package)) {
                $output->writeln('<error>' . $translator->trans('mautic.core.update.archive_no_such_file') . '</error>');

                return 1;
            }
        }

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

        if ($package) {
            $zipFile = $package;
            $version = basename($package);
        } else {
            $updateHelper = $this->getContainer()->get('mautic.helper.update');
            $update       = $updateHelper->fetchData();

            if (!isset($update['package'])) {
                $output->writeln('<error>' . $translator->trans('mautic.core.update.no_cache_data') . '</error>');

                return 1;
            }

            // Fetch the update package
            $package = $updateHelper->fetchPackage($update['package']);

            if ($package['error']) {
                $output->writeln('<error>' . $translator->trans($package['message']) . '</error>');

                return 1;
            }

            $zipFile = $this->getContainer()->getParameter('kernel.cache_dir') . '/' . basename($update['package']);
            $version = $update['version'];
        }

        $zipper  = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if ($archive !== true) {
            $output->writeln('<error>' . $translator->trans('mautic.core.update.archive_not_valid_zip') . '</error>');

            return 1;
        }

        // Extract the archive file now in place
        $zipper->extractTo($appRoot);
        $zipper->close();

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

        // Make sure we have a deleted_files list otherwise we can't process this step
        if (file_exists(__DIR__ . '/deleted_files.txt')) {
            $deletedFiles = json_decode(file_get_contents(__DIR__ . '/deleted_files.txt'), true);
            $errorLog     = array();

            // Before looping over the deleted files, add in our upgrade specific files
            $deletedFiles += array('deleted_files.txt', 'upgrade.php');

            foreach ($deletedFiles as $file) {
                $path = dirname($this->getContainer()->getParameter('kernel.root_dir')) . '/' . $file;

                // Try setting the permissions to 777 just to make sure we can get rid of the file
                @chmod($path, 0777);

                if (!@unlink($path)) {
                    // Failed to delete, reset the permissions to 644 for safety
                    @chmod($path, 0644);

                    $errorLog[] = sprintf(
                        'Failed removing the file at %s.  As this is a deleted file, you can manually remove this file.',
                        $file
                    );
                }
            }

            // If there were any errors, add them to the error log
            if (count($errorLog)) {
                // Check if the error log exists first
                if (file_exists($appRoot . '/upgrade_errors.txt')) {
                    $errors = file_get_contents($appRoot . '/upgrade_errors.txt');
                } else {
                    $errors = '';
                }

                $errors .= implode(PHP_EOL, $errorLog);

                @file_put_contents($appRoot . '/upgrade_errors.txt', $errors);
            }
        }

        // Migrate the database to the current version if migrations exist
        $iterator = new \FilesystemIterator($this->getContainer()->getParameter('kernel.root_dir') . '/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
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
        }

        // Clear the cached update data and the download package now that we've updated
        if (empty($package)) {
            @unlink($zipFile);
        } else {
            @unlink($this->getContainer()->getParameter('kernel.cache_dir') . '/lastUpdateCheck.txt');
        }
        @unlink($appRoot . '/deleted_files.txt');
        @unlink($appRoot . '/upgrade.php');

        // Update successful
        $output->writeln('<info>' . $translator->trans('mautic.core.update.update_successful', array('%version%' => $version)) . '</info>');

        return 0;
    }
}
