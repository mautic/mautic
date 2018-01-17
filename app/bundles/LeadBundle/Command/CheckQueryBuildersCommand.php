<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckQueryBuildersCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:segments:check-builders')
            ->setAliases(['mautic:segments:check-query-builders'])
            ->setDescription('Compare output of query builders for given segments')
            ->addOption('--segment-id', '-i', InputOption::VALUE_OPTIONAL, 'Set the ID of segment to process')
            ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $container->get('mautic.lead.model.list');

        $id      = $input->getOption('segment-id');
        $verbose = $input->getOption('verbose');

        $verbose = false;

        if ($id) {
            $list = $listModel->getEntity($id);
            $this->runSegment($output, $verbose, $list, $listModel);
        } else {
            $lists = $listModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (($l = $lists->next()) !== false) {
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                $this->runSegment($output, $verbose, $l, $listModel);
            }

            unset($l);

            unset($lists);
        }

        return 0;
    }

    private function runSegment($output, $verbose, $l, $listModel)
    {
        $output->writeln('<info>Running segment '.$l->getId().'...</info>');
        $output->writeln('');

        if (!$verbose) {
            ob_start();
        }

        $output->writeln('<info>old:</info>');
        $timer1    = microtime(true);
        $processed = $listModel->getVersionOld($l);
        $timer1    = round((microtime(true) - $timer1) * 1000);

        $output->writeln('<info>new:</info>');
        $timer2     = microtime(true);
        $processed2 = $listModel->getVersionNew($l);
        $timer2     = round((microtime(true) - $timer2) * 1000);

        $output->writeln('');

        if ($processed['count'] != $processed2['count'] or $processed['maxId'] != $processed2['maxId']) {
            $output->write('<error>');
        } else {
            $output->write('<info>');
        }

        $output->writeln(
            sprintf('old: c: %d, m: %d, time: %dms  <--> new: c: %d, m: %s, time: %dms',
                    $processed['count'],
                    $processed['maxId'],
                    $timer1,
                    $processed2['count'],
                    $processed2['maxId'],
                    $timer2
            )
        );

        if ($processed['count'] != $processed2['count'] or $processed['maxId'] != $processed2['maxId']) {
            $output->write('</error>');
        } else {
            $output->write('</info>');
        }

        $output->writeln('');
        $output->writeln('-------------------------------------------------------------------------');

        if (!$verbose) {
            ob_clean();
        }
    }
}
