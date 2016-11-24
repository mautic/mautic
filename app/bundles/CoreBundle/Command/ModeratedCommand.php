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

abstract class ModeratedCommand extends ContainerAwareCommand
{
    protected $checkfile;
    protected $key;
    protected $pidTable = [];

    /* @var OutputInterface $output */
    protected $output;

    /**
     * Set moderation options.
     */
    protected function configure()
    {
        $this->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function checkRunStatus(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');

        $checkFile    = $this->checkfile    = $this->getContainer()->getParameter('kernel.cache_dir').'/../script_executions.json';
        $command      = $this->getName();
        $this->output = $output;

        $fp = fopen($checkFile, 'c+');

        if (!flock($fp, LOCK_EX)) {
            $output->writeln("<error>checkRunStatus() - flock failed on {$checkFile} - taking our chances like we used to.</error>");
        }

        $this->pidTable = json_decode(fgets($fp, 8192), true);
        if (!is_array($this->pidTable)) {
            $this->pidTable = [];
        }

        $currentPid = getmypid();

        if ($force || empty($this->pidTable['in_progress'][$command]['pid'])) {
            // Just started
            $this->pidTable['in_progress'][$command]['pid'] = $currentPid;
        } else {
            // In progress
            $storedPid = $this->pidTable['in_progress'][$command]['pid'];
            if (posix_getpgid($storedPid)) {
                $output->writeln('<error>Script with pid '.$storedPid.' in progress.</error>');

                flock($fp, LOCK_UN);
                fclose($fp);

                return false;
            } else {
                // looks like the process died
                $this->pidTable['in_progress'][$command]['pid'] = $currentPid;
            }
        }

        ftruncate($fp, 0);
        rewind($fp);

        fputs($fp, json_encode($this->pidTable));
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Complete this run.
     */
    protected function completeRun()
    {
        $fp = fopen($this->checkfile, 'c+');

        flock($fp, LOCK_EX);

        $this->pidTable = json_decode(fgets($fp, 8192), true);
        if (!is_array($this->pidTable)) {
            if ($this->output) {
                $this->output->writeln('<error>completeRun() - We should have read an array of times</error>');
            }
        } else {
            // Our task has ended so remove the pid
            unset($this->pidTable['in_progress'][$this->getName()]['pid']);

            // If there's no other info stored for our task then we remove our task
            // key too, though storing the last time that we ran and how long it took
            // might be useful for audit / debugging purposes.
            if (empty($this->pidTable['in_progress'][$this->getName()])) {
                unset($this->pidTable['in_progress'][$this->getName()]);
            }

            ftruncate($fp, 0);
            rewind($fp);

            fputs($fp, json_encode($this->pidTable));
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
