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
use Symfony\Component\Filesystem\LockHandler;

abstract class ModeratedCommand extends ContainerAwareCommand
{
    const MODE_LOCK = 'lock';
    const MODE_PID  = 'pid';

    protected $checkFile;
    protected $moderationKey;
    protected $moderationTable = [];
    protected $moderationMode  = self::MODE_LOCK;
    protected $runDirectory;
    protected $lockExpiration = false;
    protected $lockHandler;
    protected $lockFile;

    /* @var OutputInterface $output */
    protected $output;

    /**
     * Set moderation options.
     */
    protected function configure()
    {
        $this
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.')
            ->addOption(
                '--timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'If getmypid() is disabled on this system, lock files will be used. This option will assume the process is dead afer the specified number of seconds and will execute anyway. This is disabled by default.',
                false
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function checkRunStatus(InputInterface $input, OutputInterface $output, $moderationKey = '')
    {
        $this->output         = $output;
        $this->lockExpiration = $input->getOption('timeout');

        // Allow multiple runs of the same command if executing different IDs, etc
        $this->moderationKey = $this->getName().$moderationKey;

        // Setup the run directory for lock/pid files
        $this->runDirectory = $this->getContainer()->getParameter('kernel.cache_dir').'/../run';
        if (!file_exists($this->runDirectory)) {
            if (!mkdir($this->runDirectory, 0755)) {
                $output->writeln('<error>'.$this->runDirectory.' could not be created.</error>');

                return false;
            }
        }

        $this->lockFile = sprintf(
            '%s/sf.%s.%s.lock',
            $this->runDirectory,
            preg_replace('/[^a-z0-9\._-]+/i', '-', $this->moderationKey),
            hash('sha256', $this->moderationKey)
        );

        // Check if the command is currently running
        if (!$this->checkStatus($input->getOption('force'))) {
            $output->writeln('<error>Script in progress. Can force execution by using --force.</error>');

            return false;
        }

        return true;
    }

    /**
     * Complete this run.
     */
    protected function completeRun()
    {
        if (self::MODE_LOCK == $this->moderationMode) {
            $this->lockHandler->release();
        }

        // Attempt to keep things tidy
        @unlink($this->lockFile);
    }

    /**
     * Determine the moderation mode avaiable to this system. Default is to use a lock file.
     *
     * @param bool $force
     *
     * @return bool
     */
    private function checkStatus($force = false)
    {
        // getmypid may be disabled and posix_getpgid is not available on Windows machines
        if (function_exists('getmypid') && function_exists('posix_getpgid')) {
            $disabled = explode(',', ini_get('disable_functions'));
            if (!in_array('getmypid', $disabled) && !in_array('posix_getpgid', $disabled)) {
                $this->moderationMode = self::MODE_PID;

                // Check if the PID is still running
                $fp = fopen($this->lockFile, 'c+');
                if (!flock($fp, LOCK_EX)) {
                    $this->output->writeln("<error>Failed to lock {$this->lockFile}.</error>");

                    return false;
                }

                $pid = fgets($fp, 8192);
                if (!$force && $pid && posix_getpgid($pid)) {
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
        }

        // Accessing PID commands is not available so use a simple lock file mechanism
        $lockHandler = $this->lockHandler = new LockHandler($this->moderationKey, $this->runDirectory);

        if (!$force && !$lockHandler->lock()) {
            // Check timestamp if $force is not requested
            if ($this->lockExpiration) {
                $fileAge = time() - filemtime($this->lockFile);

                if ($fileAge <= $this->lockExpiration) {
                    $this->output->writeln('<info>Lock expires in '.($this->lockExpiration - $fileAge).' seconds.</info>');

                    return false;
                }
            } else {
                // Lock is still in effect
                return false;
            }
        } elseif (!$force) {
            // Attempt to update the modified time just in case there was no lock but the file still exists
            @touch($this->lockFile);
        }

        return true;
    }
}
