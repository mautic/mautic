<?php

namespace Mautic\PageBundle\Helper;

use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;

class PointActionHelper
{
    public function __construct(
        private readonly HitRepository $hitRepository,
    ) {
    }

    public static function validatePageHit($eventDetails, array $action): bool
    {
        $pageHit = $eventDetails->getPage();

        if ($pageHit instanceof Page) {
            [$parent, $children] = $pageHit->getVariants();
            // use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        // If no pages are selected, the pages array does not exist
        if (isset($action['properties']['pages'])) {
            $limitToPages = $action['properties']['pages'];
        }

        // no points change
        return empty($limitToPages) || in_array($pageHitId, $limitToPages);
    }

    public function validateUrlHit($eventDetails, array $action): bool
    {
        $changePoints = [];
        $url          = $eventDetails->getUrl();
        $limitToUrl   = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl || !fnmatch($limitToUrl, $url)) {
            // no points change
            return false;
        }

        $lead          = $eventDetails->getLead();
        $urlWithSqlWC  = str_replace('*', '%', $limitToUrl);

        if (isset($action['properties']['first_time']) && true === $action['properties']['first_time']) {
            $hitStats = $this->hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            $changePoints['first_time'] = isset($hitStats['count']) && $hitStats['count'] ? false : true;
        }
        $now = new \DateTime();

        if ($action['properties']['returns_within'] || $action['properties']['returns_after']) {
            // get the latest hit only when it's needed
            $latestHit = $this->hitRepository->getLatestHit(['leadId' => $lead->getId(), 'urls' => [$urlWithSqlWC], 'second_to_last' => $eventDetails->getId()]);
        } else {
            $latestHit = null;
        }

        if ($action['properties']['accumulative_time']) {
            if (!isset($hitStats)) {
                $hitStats = $this->hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }

            if (isset($hitStats['sum'])) {
                $changePoints['accumulative_time'] = $action['properties']['accumulative_time'] <= $hitStats['sum'];
            } else {
                $changePoints['accumulative_time'] = false;
            }
        }
        if ($action['properties']['page_hits']) {
            if (!isset($hitStats)) {
                $hitStats = $this->hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }
            if (isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits']) {
                $changePoints['page_hits'] = true;
            } else {
                $changePoints['page_hits'] = false;
            }
        }
        if ($action['properties']['returns_within']) {
            $changePoints['returns_within'] = $latestHit && $now->getTimestamp() - $latestHit->getTimestamp() <= $action['properties']['returns_within'];
        }
        if ($action['properties']['returns_after']) {
            $changePoints['returns_after'] = $latestHit && $now->getTimestamp() - $latestHit->getTimestamp() >= $action['properties']['returns_after'];
        }

        // return true only if all configured options are true
        return !in_array(false, $changePoints);
    }
}
