<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

abstract class ModeratedCommand extends ContainerAwareCommand
{
    const MODE_PID   = 'pid';
    const MODE_FLOCK = 'flock';

    /**
     * @deprecated Symfony 4 Removed LockHandler and the replacement is the lock from the Lock component so there is no need for something custom
     */
    const MODE_LOCK = 'file_lock';

    protected $checkFile;
    protected $moderationKey;
    protected $moderationTable = [];
    protected $moderationMode;
    protected $runDirectory;
    protected $lockExpiration;
    protected $lockFile;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Set moderation options.
     */
    protected function configure()
    {
        $this
            ->addOption('--bypass-locking', null, InputOption::VALUE_NONE, 'Bypass locking.')
            ->addOption(
                '--timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'If getmypid() is disabled on this system, lock files will be used. This option will assume the process is dead after the specified number of seconds and will execute anyway. This is disabled by default.',
                null
            )
            ->addOption(
                '--lock_mode',
                '-x',
                InputOption::VALUE_REQUIRED,
                'Allowed value are "pid" or "flock". By default, lock will try with pid, if not available will use file system',
                self::MODE_PID
            )
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Deprecated; use --bypass-locking instead.');
    }

    protected function checkRunStatus(InputInterface $input, OutputInterface $output, $moderationKey = ''): bool
    {
        // Bypass locking
        if ((bool) $input->getOption('bypass-locking') || (bool) $input->getOption('force')) {
            return true;
        }

        $this->output = $output;

        $this->lockExpiration = $input->getOption('timeout');
        if (null !== $this->lockExpiration) {
            $this->lockExpiration = (float) $this->lockExpiration;
        }

        $this->moderationMode = $input->getOption('lock_mode');
        if (self::MODE_LOCK === $this->moderationMode) {
            // File lock is deprecated in favor of Symfony's Lock component's lock
            $this->moderationMode = 'flock';
        }
        if (!in_array($this->moderationMode, ['pid', 'flock'])) {
            $output->writeln('<error>Unknown locking method specified.</error>');

            return false;
        }

        // Allow multiple runs of the same command if executing different IDs, etc
        $this->moderationKey = $this->getName().$moderationKey;

        // Setup the run directory for lock/pid files
        $this->runDirectory = $this->getContainer()->getParameter('kernel.cache_dir').'/../run';
        if (!file_exists($this->runDirectory) && !@mkdir($this->runDirectory)) {
            // This needs to throw an exception in order to not silently fail when there is an issue
            throw new \RuntimeException($this->runDirectory.' could not be created.');
        }

        // Check if the command is currently running
        if (!$this->checkStatus()) {
            $output->writeln('<error>Script in progress. Can force execution by using --bypass-locking.</error>');

            return false;
        }

        return true;
    }

    /**
     * Complete this run.
     */
    protected function completeRun(): void
    {
        if ($this->lock) {
            $this->lock->release();
        }

        // Attempt to keep things tidy
        @unlink($this->lockFile);
    }

    private function checkStatus(): bool
    {
        if (self::MODE_PID === $this->moderationMode && $this->isPidSupported()) {
            return $this->checkPid();
        }

        return $this->checkFlock();
    }

    private function checkPid(): bool
    {
        $this->lockFile = sprintf(
            '%s/sf.%s.%s.lock',
            $this->runDirectory,
            preg_replace('/[^a-z0-9\._-]+/i', '-', $this->moderationKey),
            hash('sha256', $this->moderationKey)
        );

        // Check if the PID is still running
        $fp = fopen($this->lockFile, 'c+');
        if (!flock($fp, LOCK_EX)) {
            $this->output->writeln("<error>Failed to lock {$this->lockFile}.</error>");

            return false;
        }

        $pid = fgets($fp, 8192);
        if ($pid && posix_getpgid($pid)) {
            $this->output->writeln('<info>Script with pid '.$pid.' in progress.</info>');

            flock($fp, LOCK_UN);
            fclose($fp);

            return false;
        }

        // Write current PID to lock file
        ftruncate($fp, 0);
        rewind($fp);

        fputs($fp, getmypid());
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    private function checkFlock(): bool
    {
        $store      = new FlockStore($this->runDirectory);
        $factory    = new LockFactory($store);
        $this->lock = $factory->createLock($this->moderationKey, $this->lockExpiration);

        return $this->lock->acquire();
    }

    public function isPidSupported(): bool
    {
        // getmypid may be disabled and posix_getpgid is not available on Windows machines
        if (!function_exists('getmypid') || !function_exists('posix_getpgid')) {
            return false;
        }

        $disabled = explode(',', ini_get('disable_functions'));
        if (in_array('getmypid', $disabled) || in_array('posix_getpgid', $disabled)) {
            return false;
        }

        return true;
    }
}
