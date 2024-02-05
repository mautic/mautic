<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\InstallBundle\Configurator\Step\CheckStep;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI Command to install Mautic.
 */
class InstallCommand extends Command
{
    public const COMMAND = 'mautic:install';

    public function __construct(
        private InstallService $installer,
        private ManagerRegistry $doctrineRegistry
    ) {
        parent::__construct();
    }

    /**
     * Note: in every option (addOption()), please leave the default value empty to prevent problems with values from local.php being overwritten.
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND)
            ->setHelp('This command allows you to trigger the install process. It will try to get configuration values both from the local config file and command line options/arguments, where the latter takes precedence.')
            ->addArgument(
                'site_url',
                InputArgument::REQUIRED,
                'Site URL.',
                null
            )
            ->addArgument(
                'step',
                InputArgument::OPTIONAL,
                'Install process start index. 0 for requirements check, 1 for database, 2 for admin, 3 for configuration, 4 for final step. Each successful step will trigger the next until completion.',
                0
            )
            ->addOption(
                '--force',
                '-f',
                InputOption::VALUE_NONE,
                'Do not ask confirmation if recommendations triggered.',
                null
            )
            ->addOption(
                '--db_driver',
                null,
                InputOption::VALUE_REQUIRED,
                'Database driver.',
                null
            )
            ->addOption(
                '--db_host',
                null,
                InputOption::VALUE_REQUIRED,
                'Database host.',
                null
            )
            ->addOption(
                '--db_port',
                null,
                InputOption::VALUE_REQUIRED,
                'Database port.',
                null
            )
            ->addOption(
                '--db_name',
                null,
                InputOption::VALUE_REQUIRED,
                'Database name.',
                null
            )
            ->addOption(
                '--db_user',
                null,
                InputOption::VALUE_REQUIRED,
                'Database user.',
                null
            )
            ->addOption(
                '--db_password',
                null,
                InputOption::VALUE_REQUIRED,
                'Database password.',
                null
            )
            ->addOption(
                '--db_table_prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Database tables prefix.',
                null
            )
            ->addOption(
                '--db_backup_tables',
                null,
                InputOption::VALUE_REQUIRED,
                'Backup database tables if they exist; otherwise drop them. (true|false)',
                null
            )
            ->addOption(
                '--db_backup_prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Database backup tables prefix.',
                null
            )
            ->addOption(
                '--admin_firstname',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin first name.',
                null
            )
            ->addOption(
                '--admin_lastname',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin last name.',
                null
            )
            ->addOption(
                '--admin_username',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin username.',
                null
            )
            ->addOption(
                '--admin_email',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin email.',
                null
            )
            ->addOption(
                '--admin_password',
                null,
                InputOption::VALUE_REQUIRED,
                'Admin user.',
                null
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check Mautic is not already installed
        if ($this->installer->checkIfInstalled()) {
            $output->writeln('Mautic already installed');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $output->writeln([
            'Mautic Install',
            '==============',
            '',
        ]);

        if (!defined('IS_PHPUNIT')) {
            // Prevents querying of database tables that do not exist during the installation process
            define('MAUTIC_INSTALLER', 1);
        }

        // Build objects to pass to the install service from local.php or command line options
        $output->writeln('Parsing options and arguments...');
        $options = $input->getOptions();

        // Convert boolean options to actual booleans.
        $options['db_backup_tables'] = (bool) filter_var($options['db_backup_tables'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        /**
         * We need to have some default database parameters, as it could be the case that the
         * user didn't set them both in local.php and the command line options.
         */
        $dbParams = [
            'driver'        => 'pdo_mysql',
            'host'          => null,
            'port'          => null,
            'name'          => null,
            'user'          => null,
            'password'      => null,
            'table_prefix'  => null,
            'backup_tables' => true,
            'backup_prefix' => 'bak_',
        ];
        $adminParam = [
            'firstname' => 'Admin',
            'lastname'  => 'Mautic',
            'username'  => 'admin',
        ];
        $allParams = $this->installer->localConfigParameters();

        // Initialize DB and admin params from local.php
        foreach ((array) $allParams as $opt => $value) {
            if (str_starts_with($opt, 'db_')) {
                $dbParams[substr($opt, 3)] = $value;
            } elseif (str_starts_with($opt, 'admin_')) {
                $adminParam[substr($opt, 6)] = $value;
            }
        }

        // Initialize DB and admin params from cli options
        foreach ($options as $opt => $value) {
            if (isset($value)) {
                if (str_starts_with($opt, 'db_')) {
                    $dbParams[substr($opt, 3)] = $value;
                    $allParams[$opt]           = $value;
                } elseif (str_starts_with($opt, 'admin_')) {
                    $adminParam[substr($opt, 6)] = $value;
                }
            }
        }

        if (!empty($allParams['site_url'])) {
            $siteUrl = $allParams['site_url'];
        } else {
            $siteUrl               = $input->getArgument('site_url');
            $allParams['site_url'] = $siteUrl;
        }

        $step = (float) $input->getArgument('step');

        switch ($step) {
            default:
            case InstallService::CHECK_STEP:
                $output->writeln($step.' - Checking installation requirements...');
                $messages = $this->stepAction($this->installer, ['site_url' => $siteUrl], $step);
                if (!empty($messages)) {
                    if (isset($messages['requirements']) && !empty($messages['requirements'])) {
                        // Stop install if requirements not met
                        $output->writeln('Missing requirements:');
                        $this->handleInstallerErrors($output, $messages['requirements']);
                        $output->writeln('Install canceled');

                        return (int) -$step;
                    } elseif (isset($messages['optional']) && !empty($messages['optional'])) {
                        $output->writeln('Missing optional settings:');
                        $this->handleInstallerErrors($output, $messages['optional']);

                        if (empty($options['force'])) {
                            // Ask user to confirm install when optional settings missing

                            /** @var QuestionHelper $helper */
                            $helper   = $this->getHelper('question');
                            $question = new ConfirmationQuestion('Continue with install anyway? [yes/no]', false);

                            if (!$helper->ask($input, $output, $question)) {
                                return (int) -$step;
                            }
                        }
                    }
                }
                $output->writeln('Ready to Install!');
                // Keep on with next step
                $step = InstallService::DOCTRINE_STEP;

                // no break
            case InstallService::DOCTRINE_STEP:
                $output->writeln($step.' - Creating database...');

                /**
                 * This is needed for installations with database prefixes to work correctly.
                 */
                $connectionWrapper = $this->doctrineRegistry->getConnection();
                $connectionWrapper->initConnection($dbParams);

                $messages = $this->stepAction($this->installer, $dbParams, $step);
                if (!empty($messages)) {
                    $output->writeln('Errors in database configuration/installation:');
                    $this->handleInstallerErrors($output, $messages);

                    $output->writeln('Install canceled');

                    return (int) -$step;
                }

                $step = InstallService::DOCTRINE_STEP + .1;
                $output->writeln($step.' - Creating schema...');
                $messages = $this->stepAction($this->installer, $dbParams, $step);
                if (!empty($messages)) {
                    $output->writeln('Errors in schema configuration/installation:');
                    $this->handleInstallerErrors($output, $messages);

                    $output->writeln('Install canceled');

                    return -InstallService::DOCTRINE_STEP;
                }

                $step = InstallService::DOCTRINE_STEP + .2;
                $output->writeln($step.' - Loading fixtures...');
                $messages = $this->stepAction($this->installer, $dbParams, $step);
                if (!empty($messages)) {
                    $output->writeln('Errors in fixtures configuration/installation:');
                    $this->handleInstallerErrors($output, $messages);

                    $output->writeln('Install canceled');

                    return -InstallService::DOCTRINE_STEP;
                }

                // Keep on with next step
                $step = InstallService::USER_STEP;

                // no break
            case InstallService::USER_STEP:
                $output->writeln($step.' - Creating admin user...');
                $messages = $this->stepAction($this->installer, $adminParam, $step);
                if (!empty($messages)) {
                    $output->writeln('Errors in admin user configuration/installation:');
                    $this->handleInstallerErrors($output, $messages);

                    $output->writeln('Install canceled');

                    return (int) -$step;
                }
                // Keep on with next step
                $step = InstallService::FINAL_STEP;

                // no break
            case InstallService::FINAL_STEP:
                $output->writeln($step.' - Final steps...');
                $messages = $this->stepAction($this->installer, $allParams, $step);
                if (!empty($messages)) {
                    $output->writeln('Errors in final step:');
                    $this->handleInstallerErrors($output, $messages);

                    $output->writeln('Install canceled');

                    return (int) -$step;
                }
        }

        $output->writeln([
            '',
            '================',
            'Install complete',
            '================',
        ]);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * Controller action for install steps.
     *
     * @param InstallService $installer The install process
     * @param array          $params    The install parameters
     * @param float          $index     The step number to process
     *
     * @throws \Exception
     */
    protected function stepAction(InstallService $installer, array $params, float $index = 0): array
    {
        if ($index - floor($index) > 0) {
            $subIndex = (int) (round($index - floor($index), 1) * 10);
            $index    = floor($index);
        }
        $index = (int) $index;

        $messages = [];

        switch ($index) {
            case InstallService::CHECK_STEP:
                // Check installation requirements
                $step = $installer->getStep($index);
                if ($step instanceof CheckStep) {
                    // Set all step fields based on parameters
                    $step->site_url = $params['site_url'];
                }

                $messages['requirements'] = $installer->checkRequirements($step);
                $messages['optional']     = $installer->checkOptionalSettings($step);
                break;

            case InstallService::DOCTRINE_STEP:
                $step = $installer->getStep($index);
                if ($step instanceof DoctrineStep) {
                    // Set all step fields based on parameters
                    foreach ($step as $key => $value) {
                        if (isset($params[$key])) {
                            $step->$key = $params[$key];
                        }
                    }
                }

                if (!isset($subIndex)) {
                    // Install database
                    $messages = $installer->createDatabaseStep($step, $params);

                    break;
                }

                switch ($subIndex) {
                    case 1:
                        // Install schema
                        $messages = $installer->createSchemaStep($params);
                        break;

                    case 2:
                        // Install fixtures
                        $messages = $installer->createFixturesStep();
                        break;
                }
                break;

            case InstallService::USER_STEP:
                // Create admin user
                $messages = $installer->createAdminUserStep($params);
                break;

            case InstallService::FINAL_STEP:
                // Save final configuration
                $siteUrl  = $params['site_url'];
                $messages = $installer->createFinalConfigStep($siteUrl);
                if (empty($messages)) {
                    $installer->finalMigrationStep();
                }
                break;
        }

        return $messages;
    }

    /**
     * Handle install command errors.
     *
     * @param array<string,string> $messages
     */
    private function handleInstallerErrors(OutputInterface $output, array $messages): void
    {
        foreach ($messages as $type => $message) {
            $output->writeln("  - [$type] $message");
        }
    }

    protected static $defaultDescription = 'Installs Mautic';
}
