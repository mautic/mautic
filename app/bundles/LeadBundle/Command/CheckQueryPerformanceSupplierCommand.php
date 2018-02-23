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
use Mautic\LeadBundle\Model\ListModel;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckQueryPerformanceSupplierCommand extends ModeratedCommand
{
    /** @var Logger */
    private $logger;

    /** @var bool */
    private $skipOld = false;

    protected function configure()
    {
        $this
            ->setName('mautic:segments:check-performance')
            ->setDescription('Blah')
            ->addOption('--segment-id', '-i', InputOption::VALUE_OPTIONAL, 'Set the ID of segment to process')
            ->addOption('--skip-old', null, InputOption::VALUE_NONE, 'Skip old query builder');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('blah_2', true);
        $container    = $this->getContainer();
        $this->logger = $container->get('monolog.logger.mautic');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $container->get('mautic.lead.model.list');

        $id            = $input->getOption('segment-id');
        $verbose       = $input->getOption('verbose');
        $this->skipOld = $input->getOption('skip-old');

        $failed = $ok = 0;

        if ($id && substr($id, strlen($id) - 1, 1) != '+') {
            $list = $listModel->getEntity($id);

            if (!$list) {
                $output->writeln('<error>Segment with id "'.$id.'" not found');

                return 1;
            }
            $response = $this->runSegment($output, $verbose, $list, $listModel);
            if ($response) {
                ++$ok;
            } else {
                ++$failed;
            }
        } else {
            $lists = $listModel->getEntities(
                [
                    'iterator_mode' => true,
                    'orderBy'       => 'l.id',
                ]
            );

            while (($l = $lists->next()) !== false) {
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                if (substr($id, strlen($id) - 1, 1) == '+' and $l->getId() < intval(trim($id, '+'))) {
                    continue;
                }
                $response = $this->runSegment($output, $verbose, $l, $listModel);
                if (!$response) {
                    ++$failed;
                } else {
                    ++$ok;
                }
            }

            unset($l);

            unset($lists);
        }

        $total = $ok + $failed;
        //$output->writeln('');
        //$output->writeln(sprintf('<info>Total success rate: %d%%, %d succeeded: and <error>%s</error>%s failed...</info> ', round(($ok / $total) * 100), $ok, ($failed ? $failed : ''), (!$failed ? $failed : '')));

        return 0;
    }

    private function format_period($inputSeconds)
    {
        $now = \DateTime::createFromFormat('U.u', number_format($inputSeconds, 6, '.', ''));

        return $now->format('H:i:s.u');
    }

    private function runSegment($output, $verbose, $l, ListModel $listModel)
    {
        //$output->write('<info>Running segment '.$l->getId().'...');

        if (!$this->skipOld) {
            $this->logger->info(sprintf('Running OLD segment #%d', $l->getId()));

            $timer1    = microtime(true);
            $processed = $listModel->getVersionOld($l);
            $timer1    = microtime(true) - $timer1;
        } else {
            $processed = ['count'=>-1, 'maxId'=>-1];
            $timer1    = 0;
        }

        $this->logger->info(sprintf('Running NEW segment #%d', $l->getId()));

        $timer2     = microtime(true);
        $processed2 = $listModel->getVersionNew($l);
        $timer2     = microtime(true) - $timer2;

        $processed2 = array_shift($processed2);

        return 0;
    }
}
