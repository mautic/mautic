<?php
/**
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
    protected $executionTimes = [];
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
     * @param                 $key
     *
     * @return bool
     */
    protected function checkRunStatus(InputInterface $input, OutputInterface $output, $key)
    {
        $this->output = $output;
        $force        = $input->getOption('force');
        $timeout      = $this->getContainer()->hasParameter('mautic.command_timeout') ?
            $this->getContainer()->getParameter('mautic.command_timeout') : 1800;
        $checkFile = $this->checkfile = $this->getContainer()->getParameter('kernel.cache_dir').'/../script_executions.json';
        $command   = $this->getName();
        $this->key = $key;

        $fp = fopen($checkFile, 'c+');

        if (!flock($fp, LOCK_EX)) {
            $output->writeln("<error>checkRunStatus() - flock failed on {$checkFile} - taking our chances like we used to.</error>");
        }

        $this->executionTimes = json_decode(fgets($fp, 8192), true);
        if (!is_array($this->executionTimes)) {
            $this->executionTimes = [];
        }

        if ($force || empty($this->executionTimes['in_progress'][$command][$key])) {
            // Just started
            $this->executionTimes['in_progress'][$command][$key] = time();
        } else {
            // In progress
            $check = $this->executionTimes['in_progress'][$command][$key];

            if ($check + $timeout <= time()) {
                $this->executionTimes['in_progress'][$command][$key] = time();
            } else {
                $output->writeln('<error>Script in progress. Use -f or --force to force execution.</error>');

                flock($fp, LOCK_UN);
                fclose($fp);

                return false;
            }
        }

        ftruncate($fp, 0);
        rewind($fp);

        fputs($fp, json_encode($this->executionTimes));
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

        $this->executionTimes = json_decode(fgets($fp, 8192), true);
        if (!is_array($this->executionTimes)) {
            $this->writeln('<error>completeRun() - We should have read an array of times</error>');
        } else {
            // Our task has ended so remove the start time
            unset($this->executionTimes['in_progress'][$this->getName()][$this->key]);

            // If there's no other info stored for our task then we remove our task
            // key too, though storing the last time that we ran and how long it took
            // might be useful for audit / debugging purposes.
            if (empty($this->executionTimes['in_progress'][$this->getName()])) {
                unset($this->executionTimes['in_progress'][$this->getName()]);
            }

            ftruncate($fp, 0);
            rewind($fp);

            fputs($fp, json_encode($this->executionTimes));
            fflush($fp);
        }

        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
