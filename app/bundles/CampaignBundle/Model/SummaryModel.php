<?php

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
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SummaryModel.
 */
class SummaryModel extends AbstractCommonModel
{
    /** @var ProgressBarHelper */
    private $progressBar;

    /**
     * Collapse Event Log entities into insert/update queries for the campaign summary.
     *
     * @param $logs
     */
    public function updateSummary($logs)
    {
        $summaries = [];
        $now       = new \DateTime();
        foreach ($logs as $log) {
            /** @var LeadEventLog $log */
            $timestamp = $log->getDateTriggered()->getTimestamp();
            // Universally round down to the hour.
            $timestamp = $timestamp - ($timestamp % 3600);
            $campaign  = $log->getCampaign();
            $event     = $log->getEvent();
            $key       = $campaign->getId().'.'.$event->getId().'.'.$timestamp;
            if (!isset($summaries[$key])) {
                $dateTriggered = new \DateTime();
                $dateTriggered->setTimestamp($timestamp);
                $summary = new Summary();
                $summary->setCampaign($campaign);
                $summary->setEvent($event);
                $summary->setDateTriggered($dateTriggered);
                $summaries[$key] = $summary;
            } else {
                $summary = $summaries[$key];
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
        if ($summaries) {
            $this->getRepository()->saveEntities($summaries);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\SummaryRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Summary');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'campaign:campaigns';
    }

    /**
     * Summarize all of history.
     *
     * @param OutputInterface $output
     * @param int             $daysPerBatch
     * @param null            $maxDays
     * @param bool            $rebuild
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function summarizeDays(OutputInterface $output, $daysPerBatch = 1, $maxDays = null, $rebuild = false)
    {
        $start = null;
        if (!$rebuild) {
            /** @var \DateTime $oldestSumamryDate */
            $start = $this->getRepository()->getOldestTriggeredDate();
        }
        $start = $start ? $start : new \DateTime();

        /** @var LeadEventLog $oldestTriggeredEventLog */
        $end = $this->getCampaignLeadEventLogRepository()->getOldestTriggeredDate();
        if ($end && $end <= $start) {
            if ($maxDays && $end->diff($start)->days > $maxDays) {
                $end = clone $start;
                $end = $end->sub(new \DateInterval('P'.intval($maxDays).'D'));
            }
            $this->progressBar = ProgressBarHelper::init($output, $end->diff($start)->days);
            $this->progressBar->start();

            $interval = new \DateInterval('P'.$daysPerBatch.'D');
            $dateFrom = clone $start;
            $dateTo   = clone $start;
            do {
                $dateFrom->sub($interval);
                $output->write("\t".$dateFrom->format('Y-m-d'));
                $this->getRepository()->summarizeByDate($dateFrom, $dateTo);
                $this->progressBar->advance($daysPerBatch);
                $dateTo->sub($interval);
            } while ($end < $dateFrom);
            $this->progressBar->finish();
        }
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadEventLogRepository
     */
    public function getCampaignLeadEventLogRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
    }
}
