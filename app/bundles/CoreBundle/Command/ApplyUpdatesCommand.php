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
                $output->writeln('<error>'.$translator->trans('mautic.core.update.archive_no_such_file').'</error>');

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
                $output->writeln('<error>'.$translator->trans('mautic.core.update.no_cache_data').'</error>');

                return 1;
            }

            // Fetch the update package
            $package = $updateHelper->fetchPackage($update['package']);

            if ($package['error']) {
                $output->writeln('<error>'.$translator->trans($package['message']).'</error>');

                return 1;
            }

            $zipFile = $this->getContainer()->getParameter('kernel.cache_dir').'/'.basename($update['package']);
            $version = $update['version'];
        }

        $zipper  = new \ZipArchive();
        $archive = $zipper->open($zipFile);

        if ($archive !== true) {
            // Get the exact error
            switch ($archive) {
                case \ZipArchive::ER_EXISTS:
                    $error = 'mautic.core.update.archive_file_exists';
                    break;
                case \ZipArchive::ER_INCONS:
                case \ZipArchive::ER_INVAL:
                case \ZipArchive::ER_MEMORY:
                    $error = 'mautic.core.update.archive_zip_corrupt';
                    break;
                case \ZipArchive::ER_NOENT:
                    $error = 'mautic.core.update.archive_no_such_file';
                    break;
                case \ZipArchive::ER_NOZIP:
                    $error = 'mautic.core.update.archive_not_valid_zip';
                    break;
                case \ZipArchive::ER_READ:
                case \ZipArchive::ER_SEEK:
                case \ZipArchive::ER_OPEN:
                default:
                    $error = 'mautic.core.update.archive_could_not_open';
                    break;
            }

            $output->writeln('<error>'.$translator->trans('mautic.core.update.error', array('%error%' => $translator->trans($error))).'</error>');

            return 1;
        }

        // Extract the archive file now in place
        $output->writeln('<info>Extracting zip...</info>');

        if (!$zipper->extractTo($appRoot)) {
            $output->writeln(
                '<error>'.$translator->trans(
                    'mautic.core.update.error',
                    array('%error%' => $translator->trans('mautic.core.update.error_extracting_package'))
                ).'</error>'
            );

            return 1;
        }

        $zipper->close();

        // Clear the dev and prod cache instances to reset the system
        $output->writeln('<info>Clearing the cache...</info>');
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
        if (file_exists(__DIR__.'/deleted_files.txt')) {
            $output->writeln('<info>Remove deleted files...</info>');
            $deletedFiles = json_decode(file_get_contents(__DIR__.'/deleted_files.txt'), true);
            $errorLog     = array();

            // Before looping over the deleted files, add in our upgrade specific files
            $deletedFiles += array('deleted_files.txt', 'upgrade.php');

            foreach ($deletedFiles as $file) {
                $path = dirname($this->getContainer()->getParameter('kernel.root_dir')).'/'.$file;

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
                if (file_exists($appRoot.'/upgrade_errors.txt')) {
                    $errors = file_get_contents($appRoot.'/upgrade_errors.txt');
                } else {
                    $errors = '';
                }

                $errors .= implode(PHP_EOL, $errorLog);

                @file_put_contents($appRoot.'/upgrade_errors.txt', $errors);
            }
        }

        // Update languages
        $supportedLanguages = $this->getContainer()->get('mautic.factory')->getParameter('supported_languages');

        // If there is only one language, assume it is 'en_US' and skip this
        if (count($supportedLanguages) > 1) {
            /** @var \Mautic\CoreBundle\Helper\LanguageHelper $languageHelper */
            $languageHelper = $this->getContainer()->get('mautic.factory')->getHelper('language');

            // First, update the cached language data
            $result = $languageHelper->fetchLanguages(true);

            // Only continue if not in error
            if (!isset($result['error'])) {
                foreach ($supportedLanguages as $locale => $name) {
                    // We don't need to update en_US, that comes with the main package
                    if ($locale == 'en_US') {
                        continue;
                    }

                    // Update time
                    $extractResult = $languageHelper->extractLanguagePackage($locale);

                    if ($extractResult['error']) {
                        $output->writeln(
                            '<error>'.$translator->trans('mautic.core.update.error_updating_language', array('%language%' => $name)).'</error>'
                        );
                    }
                }
            }
        }

        // Migrate the database to the current version if migrations exist
        $output->writeln('<info>Migrate database schema...</info>');
        $iterator = new \FilesystemIterator($this->getContainer()->getParameter('kernel.root_dir').'/migrations', \FilesystemIterator::SKIP_DOTS);

        if (iterator_count($iterator)) {
            $command = $this->getApplication()->find('doctrine:migrations:migrate');
            $input = new ArrayInput(array(
                'command'          => 'doctrine:migrations:migrate',
                '--env'            => $options['env'],
                '--no-interaction' => true
            ));
            $exitCode = $command->run($input, $output);

            if ($exitCode !== 0) {
                $output->writeln('<error>'.$translator->trans('mautic.core.update.error_performing_migration').'</error>');
            }
        }

        $output->writeln('<info>Cleaning up...</info>');
        // Clear the cached update data and the download package now that we've updated
        if (empty($package)) {
            @unlink($zipFile);
        } else {
            @unlink($this->getContainer()->getParameter('kernel.cache_dir').'/lastUpdateCheck.txt');
        }
        @unlink($appRoot.'/deleted_files.txt');
        @unlink($appRoot.'/upgrade.php');

        // Update successful
        $output->writeln('<info>'.$translator->trans('mautic.core.update.update_successful', array('%version%' => $version)).'</info>');

        return 0;
    }
}
