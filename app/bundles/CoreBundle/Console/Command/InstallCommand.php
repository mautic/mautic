<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Console\Command;

use Mautic\CoreBundle\Console\Output\ConsoleDatetimeOutput;
use Mautic\CoreBundle\Exception\InstallationException;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Installer\Installer;
use Mautic\CoreBundle\Installer\InstallerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class InstallCommand extends Command
{
    private const DEFAULT_CONFIG_DIR = 'config';
    private const DEFAULT_VAR_DIR = 'var';
    private const DEFAULT_LOGS_DIR = 'logs';

    protected static $defaultName = 'mautic:install';

    private InstallerFactory $installerFactory;
    private PathsHelper $pathsHelper;

    public function __construct(InstallerFactory $installerFactory, PathsHelper $pathsHelper)
    {
        parent::__construct();

        $this->installerFactory = $installerFactory;
        $this->pathsHelper = $pathsHelper;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Installs Mautic')
            ->addOption('no-interaction', null, InputOption::VALUE_NONE, 'Do not ask any interactive question')
            ->addOption('skip-requirements', null, InputOption::VALUE_NONE, 'Skip requirements check')
            ->addOption('skip-config', null, InputOption::VALUE_NONE, 'Skip configuration')
            ->addOption('skip-database', null, InputOption::VALUE_NONE, 'Skip database setup')
            ->addOption('skip-assets', null, InputOption::VALUE_NONE, 'Skip assets installation')
            ->addOption('skip-mailer', null, InputOption::VALUE_NONE, 'Skip mailer setup')
            ->addOption('skip-queue', null, InputOption::VALUE_NONE, 'Skip queue setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-supervisor', null, InputOption::VALUE_NONE, 'Skip queue supervisor setup')
            ->addOption('skip-queue-cron', null, InputOption::VALUE_NONE, 'Skip queue cron setup')
            ->addOption('skip-queue-scheduler', null, InputOption::VALUE_NONE, 'Skip queue scheduler setup')
            ->addOption('skip-queue-consumer', null, InputOption::VALUE_NONE, 'Skip queue consumer setup')
            ->addOption('skip-queue-producer', null, InputOption::VALUE_NONE, 'Skip queue producer setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE_NONE, 'Skip queue worker setup')
            ->addOption('skip-queue-worker-cron', null, InputOption::VALUE_NONE, 'Skip queue worker cron setup')
            ->addOption('skip-queue-worker-supervisor', null, InputOption::VALUE_NONE, 'Skip queue worker supervisor setup')
            ->addOption('skip-queue-worker-scheduler', null, InputOption::VALUE_NONE, 'Skip queue worker scheduler setup')
            ->addOption('skip-queue-worker-consumer', null, InputOption::VALUE_NONE, 'Skip queue worker consumer setup')
            ->addOption('skip-queue-worker-producer', null, InputOption::VALUE_NONE, 'Skip queue worker producer setup')
            ->addOption('skip-queue-worker', null, InputOption::VALUE