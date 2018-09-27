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
use Symfony\Component\Console\Output\Output;

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
     * @param Output $output
     */
    public function summarizeHistory(Output $output)
    {
        /** @var \DateTime $oldestSumamryDate */
        $start = $this->getRepository()->getOldestTriggered();

        /** @var LeadEventLog $oldestTriggeredEventLog */
        $oldestTriggeredEventLogs = $this->getCampaignLeadEventLogRepository()->getEntities(
            [
                'limit'            => 1,
                'orderBy'          => 'll.dateTriggered, ll.id',
                'orderByDir'       => 'ASC',
                'ignore_paginator' => true,
            ]
        );
        if ($oldestTriggeredEventLog = reset($oldestTriggeredEventLogs)) {
            $end               = $oldestTriggeredEventLog->getDateTriggered();
            $totalHours        = abs(intval(($start->getTimestamp() - $end->getTimestamp()) / 60 / 60));
            $this->progressBar = ProgressBarHelper::init($output, $totalHours);
            $this->progressBar->start();

            // Forcibly update or drop the first hour of summaries.

            // Insert/update/increment other hours.

            // @todo - Batch query to run through events prior to that moment to write/overwrite the summary entries.

            // @todo - Get up to 100k rows, starting with the ID of the last one, and go backward till there are no more.

            $do = 'THITYNGSS';
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
