<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Symfony\Component\Console\Output\OutputInterface;

class SummaryModel extends AbstractCommonModel
{
    /**
     * @var ProgressBarHelper
     */
    private $progressBar;

    /**
     * @var array
     */
    private $summaries = [];

    /**
     * Collapse Event Log entities into insert/update queries for the campaign summary.
     */
    public function updateSummary(iterable $logs): void
    {
        $now = new \DateTime();
        foreach ($logs as $log) {
            /** @var LeadEventLog $log */
            $timestamp = $log->getDateTriggered()->getTimestamp();
            // Universally round down to the hour.
            $timestamp = $timestamp - ($timestamp % 3600);
            $campaign  = $log->getCampaign();
            $event     = $log->getEvent();
            $key       = $campaign->getId().'.'.$event->getId().'.'.$timestamp;
            if (!isset($this->summaries[$key])) {
                $dateTriggered = new \DateTime();
                $dateTriggered->setTimestamp($timestamp);
                $summary = new Summary();
                $summary->setCampaign($campaign);
                $summary->setEvent($event);
                $summary->setDateTriggered($dateTriggered);
                $this->summaries[$key] = $summary;
            } else {
                $summary = $this->summaries[$key];
            }

            if ($log->getIsScheduled() && $log->getTriggerDate() > $now) {
                $summary->setScheduledCount($summary->getScheduledCount() + 1);
            } elseif ($log->getNonActionPathTaken()) {
                $summary->setNonActionPathTakenCount($summary->getNonActionPathTakenCount() + 1);
            } elseif ($log->getFailedLog()) {
                $summary->setFailedCount($summary->getFailedCount() + 1);
            } elseif ($log->getSystemTriggered()) {
                $summary->setTriggeredCount($summary->getTriggeredCount() + 1);
            }
        }

        if (count($this->summaries) >= 100) {
            $this->persistSummaries();
        }
    }

    public function getRepository(): SummaryRepository
    {
        return $this->em->getRepository(Summary::class);
    }

    public function getPermissionBase(): string
    {
        return 'campaign:campaigns';
    }

    /**
     * Summarize all of history.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function summarize(OutputInterface $output, int $hoursPerBatch = 1, int $maxHours = null, bool $rebuild = false): void
    {
        $start = null;
        if (!$rebuild) {
            $start = $this->getRepository()->getOldestTriggeredDate();
        }
        // Start with the last complete hour.
        $start = $start ? $start : new \DateTime('-1 hour');
        $start->setTimestamp($start->getTimestamp() - ($start->getTimestamp() % 3600));

        $end = $this->getCampaignLeadEventLogRepository()->getOldestTriggeredDate();

        if (!$end) {
            $output->writeln('There are no records in the campaign lead event log table. Nothng to summarize.');

            return;
        }

        $end = $end->setTimestamp($end->getTimestamp() - ($end->getTimestamp() % 3600));

        if ($end && $end <= $start) {
            $hours = ($end->diff($start)->days * 24) + $end->diff($start)->h;
            if ($maxHours && $hours > $maxHours) {
                $end = clone $start;
                $end = $end->sub(new \DateInterval('PT'.intval($maxHours).'H'));
            }
            $this->progressBar = ProgressBarHelper::init($output, $hours);
            $this->progressBar->start();

            $interval = new \DateInterval('PT'.$hoursPerBatch.'H');
            $dateFrom = clone $start;
            $dateTo   = clone $start;
            do {
                $dateFrom = $dateFrom->sub($interval);
                $output->write("\t".$dateFrom->format('Y-m-d H:i:s'));
                $this->getRepository()->summarize($dateFrom, $dateTo);
                $this->progressBar->advance($hoursPerBatch);
                $dateTo = $dateTo->sub($interval);
            } while ($end < $dateFrom);
            $this->progressBar->finish();
        }
    }

    public function getCampaignLeadEventLogRepository(): LeadEventLogRepository
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function persistSummaries(): void
    {
        if ($this->summaries) {
            $this->getRepository()->saveEntities($this->summaries);
            $this->summaries = [];
            $this->em->clear(Summary::class);
        }
    }
}
