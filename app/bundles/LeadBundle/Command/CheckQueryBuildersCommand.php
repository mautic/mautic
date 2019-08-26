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
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckQueryBuildersCommand extends ModeratedCommand
{
    /** @var Logger */
    private $logger;

    /** @var bool */
    private $skipOld = false;

    protected function configure()
    {
        $this
            ->setName('mautic:segments:check-builders')
            ->setDescription('Compare output of query builders for given segments')
            ->addOption('--segment-id', '-i', InputOption::VALUE_OPTIONAL, 'Set the ID of segment to process')
            ->addOption('--skip-old', null, InputOption::VALUE_NONE, 'Skip old query builder');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        $total = $total ?: 1; //prevent division be zero error

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Total success rate: %d%%, %d succeeded: and <error>%s</error>%s failed...</info> ',
                round(($ok / $total) * 100),
                $ok,
                ($failed ? $failed : ''),
                (!$failed ? $failed : '')
            )
        );

        return $failed ? 1 : 0;
    }

    private function format_period($inputSeconds)
    {
        $now = \DateTime::createFromFormat('U.u', number_format($inputSeconds, 6, '.', ''));

        return $now->format('H:i:s.u');
    }

    private function runSegment(OutputInterface $output, $verbose, LeadList $l, ListModel $listModel)
    {
        if (!$l->isPublished()) {
            $msg = sprintf('Segment #%d is not published', $l->getId());
            $output->writeln($msg);
            $this->logger->info($msg);

            return true;
        }

        $output->write('<info>Running segment '.$l->getId().'...');

        if (!$this->skipOld) {
            $this->logger->info(sprintf('Running OLD segment #%d', $l->getId()));

            $timer1    = microtime(true);
            $processed = $listModel->getVersionOld($l);
            $timer1    = microtime(true) - $timer1;
        } else {
            $processed = ['count' => -1, 'maxId' => -1];
            $timer1    = 0;
        }

        $this->logger->info(sprintf('Running NEW segment #%d', $l->getId()));

        $timer2 = microtime(true);
        try {
            $processed2 = $listModel->getVersionNew($l);
        } catch (SegmentNotFoundException $exception) {
            $processed2 = [
                [
                    'count' => -1,
                    'maxId' => -1,
                ],
            ];
            $output->write('<error>'.$exception->getMessage().' </error>');
        } catch (FieldNotFoundException $exception) {
            $processed2 = [
                [
                    'count' => -1,
                    'maxId' => -1,
                ],
            ];

            $output->write('<error>'.$exception->getMessage().' </error>');
        }

        $timer2 = microtime(true) - $timer2;

        $processed2 = array_shift($processed2);

        if ((intval($processed['count']) != intval($processed2['count'])) or (intval($processed['maxId']) != intval($processed2['maxId']))) {
            $output->write('<error>FAILED - ');
        } else {
            $output->write('<info>OK - ');
        }

        $output->write(
            sprintf(
                'old: c: %d, m: %d, time: %s(est)  <--> new: c: %d, m: %s, time: %s',
                $processed['count'],
                $processed['maxId'],
                $this->format_period($timer1),
                $processed2['count'],
                $processed2['maxId'],
                $this->format_period($timer2)
            )
        );

        if ((intval($processed['count']) != intval($processed2['count'])) or (intval($processed['maxId']) != intval($processed2['maxId']))) {
            $output->writeln('</error>');
        } else {
            $output->writeln('</info>');
        }

        $failed = ((intval($processed['count']) != intval($processed2['count'])) or (intval($processed['maxId']) != intval($processed2['maxId'])));

        return !$failed;
    }
}
