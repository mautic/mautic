<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
    protected $executionTimes = array();

    /**
     * Set moderation options
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
        $force     = $input->getOption('force');
        $timeout   = $this->getContainer()->hasParameter('mautic.command_timeout') ?
            $this->getContainer()->getParameter('mautic.command_timeout') : 1800;
        $checkFile = $this->checkfile = $this->getContainer()->getParameter('kernel.cache_dir').'/../script_executions.json';
        $command   = $this->getName();
        $this->key = $key;

        if (file_exists($checkFile)) {
            // Get the time in the file
            $this->executionTimes = json_decode(file_get_contents($checkFile), true);
            if (!is_array($this->executionTimes)) {
                $this->executionTimes = array();
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

                    return false;
                }
            }
        } else {
            // Just started
            $this->executionTimes['in_progress'][$command][$key] = time();
        }

        file_put_contents($this->checkfile, json_encode($this->executionTimes));

        return true;
    }

    /**
     * Complete this run
     */
    protected function completeRun()
    {
        unset($this->executionTimes['in_progress'][$this->getName()][$this->key]);
        file_put_contents($this->checkfile, json_encode($this->executionTimes));
    }
}