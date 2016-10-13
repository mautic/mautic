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

use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI Command to update the application.
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
            ->setDefinition(
                [
                    new InputOption(
                        'force', null, InputOption::VALUE_NONE,
                        'Bypasses the verification check.'
                    ),
                    new InputOption('update-package', 'p', InputOption::VALUE_OPTIONAL, 'Optional full path to the update package to apply.'),
                ]
            )
            ->setHelp(
                <<<'EOT'
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
            /** @var \Symfony\Component\Console\Helper\SymfonyQuestionHelper $helper */
            $helper   = $this->getHelperSet()->get('question');
            $question = new ConfirmationQuestion($translator->trans('mautic.core.update.confirm_application_update').' ', false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln($translator->trans('mautic.core.update.aborted'));

                return 0;
            }
        }

        // Start a progress bar, don't give a max number of steps because it is conditional
        $progressBar = ProgressBarHelper::init($output);
        $progressBar->setFormat('Step %current% [%bar%] <info>%message%</info>');

        if ($package) {
            $progressBar->setMessage($translator->trans('mautic.core.command.update.step.loading_package').'                  ');
            $progressBar->advance();
            $zipFile = $package;
            $version = basename($package);
        } else {
            $progressBar->setMessage($translator->trans('mautic.core.command.update.step.loading_update_information').'                  ');
            $progressBar->advance();

            $updateHelper = $this->getContainer()->get('mautic.helper.update');
            $update       = $updateHelper->fetchData();

            if (!isset($update['package'])) {
                $output->writeln("\n\n<error>".$translator->trans('mautic.core.update.no_cache_data').'</error>');

                return 1;
            }

            $progressBar->setMessage($translator->trans('mautic.core.command.update.step.download_update_package').'                  ');
            $progressBar->advance();

            // Fetch the update package
            $package = $updateHelper->fetchPackage($update['package']);

            if ($package['error']) {
                $output->writeln("\n\n<error>".$translator->trans($package['message']).'</error>');

                return 1;
            }

            $zipFile = $this->getContainer()->getParameter('kernel.cache_dir').'/'.basename($update['package']);
            $version = $update['version'];
        }

        $progressBar->setMessage($translator->trans('mautic.core.command.update.step.validate_update_package').'                  ');
        $progressBar->advance();

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

            $output->writeln("\n\n<error>".$translator->trans('mautic.core.update.error', ['%error%' => $translator->trans($error)]).'</error>');

            return 1;
        }

        // Extract the archive file now in place
        $progressBar->setMessage($translator->trans('mautic.core.update.step.extracting.package').'                  ');
        $progressBar->advance();

        if (!$zipper->extractTo($appRoot)) {
            $output->writeln(
                "\n\n<error>".$translator->trans(
                    'mautic.core.update.error',
                    ['%error%' => $translator->trans('mautic.core.update.error_extracting_package')]
                ).'</error>'
            );

            return 1;
        }

        $zipper->close();

        // Define this just in case
        defined('MAUTIC_ENV') or define('MAUTIC_ENV', (isset($options['env'])) ? $options['env'] : 'prod');

        // Make sure we have a deleted_files list otherwise we can't process this step
        if (file_exists($appRoot.'/deleted_files.txt')) {
            $progressBar->setMessage($translator->trans('mautic.core.update.remove.deleted.files').'                  ');
            $progressBar->advance();

            $deletedFiles = json_decode(file_get_contents($appRoot.'/deleted_files.txt'), true);
            $errorLog     = [];

            // Before looping over the deleted files, add in our upgrade specific files
            $deletedFiles += ['deleted_files.txt', 'upgrade.php'];

            foreach ($deletedFiles as $file) {
                $path = $appRoot.'/'.$file;

                if (file_exists($path)) {
                    // Try setting the permissions to 777 just to make sure we can get rid of the file
                    @chmod($path, 0777);

                    if (!@unlink($path)) {
                        // Failed to delete, reset the permissions to 644 for safety
                        @chmod($path, 0644);

                        $errorLog[] = $translator->trans('mautic.core.update.error.removing.file', ['%path%' => $file]);
                    }
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

        // Clear the dev and prod cache instances to reset the system
        $progressBar->setMessage($translator->trans('mautic.core.update.clear.cache').'                  ');
        $progressBar->advance();

        $cacheHelper = $this->getContainer()->get('mautic.helper.cache');
        $cacheHelper->nukeCache();

        // Update languages
        $supportedLanguages = $this->getContainer()->get('mautic.factory')->getParameter('supported_languages');

        // If there is only one language, assume it is 'en_US' and skip this
        if (count($supportedLanguages) > 1) {
            $progressBar->setMessage($translator->trans('mautic.core.command.update.step.update_languages'.'                  '));
            $progressBar->advance();

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
                            "\n\n<error>".$translator->trans('mautic.core.update.error_updating_language', ['%language%' => $name]).'</error>'
                        );
                    }
                }
            }
        }

        // Migrate the database to the current version
        $progressBar->setMessage($translator->trans('mautic.core.update.migrating.database.schema'.'                  '));
        $progressBar->advance();

        $migrationApplication = new Application($this->getContainer()->get('kernel'));
        $migrationApplication->setAutoExit(false);
        $migrationCommandArgs = new ArgvInput(['console', 'doctrine:migrations:migrate', '--quiet', '--no-interaction']);
        $migrationCommandArgs->setInteractive(false);
        $migrateExitCode = $migrationApplication->run($migrationCommandArgs, new NullOutput());
        unset($migrationApplication);

        $progressBar->setMessage($translator->trans('mautic.core.update.step.wrapping_up'.'                  '));
        $progressBar->advance();

        // Clear the cached update data and the download package now that we've updated
        if (empty($package)) {
            @unlink($zipFile);
        } else {
            @unlink($this->getContainer()->getParameter('kernel.cache_dir').'/lastUpdateCheck.txt');
        }
        @unlink($appRoot.'/deleted_files.txt');
        @unlink($appRoot.'/upgrade.php');

        // Update successful
        $progressBar->setMessage($translator->trans('mautic.core.update.update_successful', ['%version%' => $version]).'                  ');
        $progressBar->finish();

        // Check for a post install message
        if ($postMessage = $this->getContainer()->get('session')->get('post_upgrade_message', false)) {
            $postMessage = strip_tags($postMessage);
            $this->getContainer()->get('session')->remove('post_upgrade_message');
            $output->writeln("\n\n<info>$postMessage</info>");
        }

        // Output the error (if exists) from the migrate command after we've finished the progress bar
        if ($migrateExitCode !== 0) {
            $output->writeln("\n\n<error>".$translator->trans('mautic.core.update.error_performing_migration').'</error>');
        }

        return 0;
    }
}
