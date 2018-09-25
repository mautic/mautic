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

use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CoreBundle\Model\AbstractCommonModel;

/**
 * Class SummaryModel.
 */
class SummaryModel extends AbstractCommonModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\LeadEventLogRepository
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
     * Collapse Event Log entities into insert/update queries for the campaign summary.
     *
     * @param $logs
     */
    public function updateSummary($logs)
    {
        $summaries = [];
        foreach ($logs as $log) {
            if ($log->getIsScheduled()) {
                // We are intentionally excluding scheduled events from charts and summaries at this time.
                continue;
            }
            // Universally round down to the hour.
            $timestamp = $log->getDateTriggered()->getTimestamp();
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

            if ($log->getSystemTriggered()) {
                $summary->setTriggeredCount($summary->getTriggeredCount() + 1);
            }

            if ($log->getNonActionPathTaken()) {
                $summary->setNonActionPathTakenCount($summary->getNonActionPathTakenCount() + 1);
            }

            if ($log->getFailedLog()) {
                $summary->setFailedCount($summary->getFailedCount() + 1);
            }

            if ($log->getId()) {
                // @todo - handle changes a bit differently for log entries already summarized.
                $summary->setNew(false);
            }
        }
        if ($summaries) {
            $this->getRepository()->saveEntities($summaries);
        }
    }
}
