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

use DateTimeInterface;
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
     * @var array
     */
    private $summaries = [];

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * SummaryModel constructor.
     */
    public function __construct(LeadEventLogRepository $leadEventLogRepository)
    {
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    /**
     * Collapse Event Log entities into insert/update queries for the campaign summary.
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSummary(iterable $logs): void
    {
        $now = new \DateTime();
        foreach ($logs as $log) {
            /** @var LeadEventLog $log */
            $timestamp = $log->getDateTriggered()->getTimestamp();
            // Universally round down to the hour.
            $timestamp -= ($timestamp % 3600);
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

            $this->setSummaryLogCountsProcessed($summary);
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
        $start = $start ?? new \DateTime();
        $start->setTimestamp($start->getTimestamp() - ($start->getTimestamp() % 3600));

        $end = $this->getCampaignLeadEventLogRepository()->getOldestTriggeredDate();

        if (!$end) {
            $output->writeln('There are no records in the campaign lead event log table. Nothing to summarize.');

            return;
        }

        $end = $end->setTimestamp($end->getTimestamp() - ($end->getTimestamp() % 3600));

        if ($end && $end <= $start) {
            if ($rebuild) {
                $this->prepareRebuildSummary();
            }

            $hours = ($end->diff($start)->days * 24) + $end->diff($start)->h;
            if ($maxHours && $hours > $maxHours) {
                $end = clone $start;
                $end = $end->sub(new \DateInterval('PT'.intval($maxHours).'H'));
            }
            $progressBar = ProgressBarHelper::init($output, $hours);
            $progressBar->start();

            $interval = new \DateInterval('PT'.$hoursPerBatch.'H');
            $dateFrom = clone $start;
            $dateTo   = (clone $start)->modify('-1 second');
            do {
                $dateFrom          = $dateFrom->sub($interval);
                $dateFromFormatted = $dateFrom->format('Y-m-d H:i:s');
                $dateToFormatted   = $dateTo->format('Y-m-d H:i:s');
                $output->write("\t".$dateFromFormatted.' - '.$dateToFormatted);
                $summaryId = $this->getRepository()->summarize($dateFrom, $dateTo);

                $this->logger->debug(
                    'Processed summary #ID: '.$summaryId.
                    ' Date from: '.$dateFromFormatted.
                    ' Date to: '.$dateToFormatted
                );

                $this->processLogCountsProcessed($summaryId, $dateFrom, $dateTo);
                $progressBar->advance($hoursPerBatch);
                $dateTo = $dateTo->sub($interval);
            } while ($end < $dateFrom);
            $progressBar->finish();
        }

        $output->writeln("\n".'<info>Summary complete</info>');
    }

    public function getCampaignLeadEventLogRepository(): LeadEventLogRepository
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
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

    /**
     * @throws \Doctrine\DBAL\Cache\CacheException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function processLogCountsProcessed(int $summaryId, \DateTime $dateFrom, \DateTime $dateTo): void
    {
        $summary = $this->getRepository()->find($summaryId);

        if (!$summary instanceof Summary) {
            return;
        }

        $campaignLogCountsProcessed = $this->getLogCountsProcessed($summary, $dateFrom, $dateTo);
        $this->updateLogCountsProcessed($summary, $campaignLogCountsProcessed);
    }

    /**
     * @throws \Doctrine\DBAL\Cache\CacheException
     */
    private function getLogCountsProcessed(
        Summary $summary,
        DateTimeInterface $dateFrom,
        DateTimeInterface $dateTo
    ): array {
        $campaignId = $summary->getCampaign()->getId();
        $eventId    = $summary->getEvent()->getId();

        return $this->leadEventLogRepository->getCampaignLogCounts(
            $campaignId,
            false,
            false,
            false,
            $dateFrom,
            $dateTo,
            $eventId
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateLogCountsProcessed(Summary $summary, array $campaignLogCountsProcessed): void
    {
        $summaryId = $summary->getId();
        $eventId   = $summary->getEvent()->getId();

        $logCountsProcessed = isset($campaignLogCountsProcessed[$eventId]) ? array_sum($campaignLogCountsProcessed[$eventId]) : 0;
        $this->getRepository()->updateLogCountsProcessed($summaryId, $logCountsProcessed);
        $this->logger->debug(
            'Updated summary #ID: '.$summaryId.
            ' log_counts_processed = '.$logCountsProcessed
        );
    }

    /**
     * @throws \Doctrine\DBAL\Cache\CacheException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setSummaryLogCountsProcessed(Summary $summary): void
    {
        $dateFrom = $summary->getDateTriggered();
        $dateTo   = (clone $dateFrom)->modify('+1 hour -1 second');

        $campaignLogCountsProcessed = $this->getLogCountsProcessed($summary, $dateFrom, $dateTo);

        $eventId            = $summary->getEvent()->getId();
        $logCountsProcessed = isset($campaignLogCountsProcessed[$eventId])
            ? array_sum($campaignLogCountsProcessed[$eventId])
            : 0;

        $summary->setLogCountsProcessed($logCountsProcessed);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function prepareRebuildSummary(): void
    {
        $this->getRepository()->deleteAll();
    }
}
