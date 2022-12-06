<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Model;

use DateInterval;
use DateTime;
use Doctrine\DBAL\DBALException;
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
    private $logData = [];

    /**
     * Collapse Event Log entities into insert/update queries for the campaign summary.
     *
     * @throws DBALException
     */
    public function updateSummary(iterable $logs): void
    {
        $now = new DateTime();

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            if (!$log->getDateTriggered()) {
                // This shouldn't normally happen but it's possible to have a log without a date triggered
                // as it is a nullable field and it can be created without date triggered for example via API.
                continue;
            }

            $timestamp = $log->getDateTriggered()->getTimestamp();
            $timestamp -= ($timestamp % 3600);
            $dateFrom = ($now)->setTimestamp($timestamp);
            $dateTo   = (clone $dateFrom)->modify('+1 hour -1 second');

            $campaign  = $log->getCampaign();
            $event     = $log->getEvent();
            $key       = $campaign->getId().'.'.$event->getId().'.'.$timestamp;

            $this->logData[$key] = [
                'campaignId' => $campaign->getId(),
                'eventId'    => $event->getId(),
                'dateFrom'   => $dateFrom,
                'dateTo'     => $dateTo,
            ];
        }

        if (count($this->logData) >= 100) {
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
     * @throws DBALException
     */
    public function summarize(OutputInterface $output, int $hoursPerBatch = 1, int $maxHours = null, bool $rebuild = false): void
    {
        $start = null;

        if (!$rebuild) {
            $start = $this->getRepository()->getOldestTriggeredDate();
        }

        // Start with the current hour.
        $start = $start ?? new DateTime('+1 hour');
        $start->setTimestamp($start->getTimestamp() - ($start->getTimestamp() % 3600));
        $end = $this->getCampaignLeadEventLogRepository()->getOldestTriggeredDate();

        if (!$end) {
            $output->writeln('There are no records in the campaign lead event log table. Nothing to summarize.');

            return;
        }

        $end       = $end->setTimestamp($end->getTimestamp() - ($end->getTimestamp() % 3600));
        $startedAt = new DateTime();
        $output->writeln('<comment>Started at: '.$startedAt->format('Y-m-d H:i:s').'</comment>');

        if ($end <= $start) {
            $hours = ($end->diff($start)->days * 24) + $end->diff($start)->h;

            if ($maxHours && $hours > $maxHours) {
                $end = clone $start;
                $end = $end->sub(new DateInterval('PT'.$maxHours.'H'));
            }

            $progressBar = ProgressBarHelper::init($output, $hours);
            $progressBar->start();

            $interval = new DateInterval('PT'.$hoursPerBatch.'H');
            $dateFrom = clone $start;
            $dateTo   = (clone $start)->modify('-1 second');

            do {
                $dateFrom          = $dateFrom->sub($interval);
                $dateFromFormatted = $dateFrom->format('Y-m-d H:i:s');
                $dateToFormatted   = $dateTo->format('Y-m-d H:i:s');
                $output->write("\t".$dateFromFormatted.' - '.$dateToFormatted);
                $this->getRepository()->summarize($dateFrom, $dateTo);
                $progressBar->advance($hoursPerBatch);
                $dateTo = $dateTo->sub($interval);
            } while ($end < $dateFrom);

            $progressBar->finish();

            $output->writeln("\n".'<info>Updating summary for log counts processed</info>');
        }

        $this->outputProcessTime($startedAt, $output);
    }

    public function getCampaignLeadEventLogRepository(): LeadEventLogRepository
    {
        return $this->em->getRepository(LeadEventLog::class);
    }

    /**
     * @throws DBALException
     */
    public function persistSummaries(): void
    {
        foreach ($this->logData as $log) {
            $dateFrom   = $log['dateFrom'];
            $dateTo     = $log['dateTo'];
            $campaignId = $log['campaignId'];
            $eventId    = $log['eventId'];
            $this->getRepository()->summarize($dateFrom, $dateTo, $campaignId, $eventId);
        }
    }

    private function outputProcessTime(DateTime $startedAt, OutputInterface $output): void
    {
        $endedAt = new DateTime();
        $output->writeln("\n".'<comment>Ended at: '.$endedAt->format('Y-m-d H:i:s').'</comment>');
        $completedInterval = $startedAt->diff($endedAt);
        $output->writeln('<info>Summary completed in: '.$completedInterval->format('%H:%I:%S').'</info>');
    }
}
