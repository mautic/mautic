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

        if (!$limitToUrl) {
            return false;
        }

        $urlMatches   = fnmatch($limitToUrl, $url);
        $lead         = $eventDetails->getLead();
        $urlWithSqlWC = str_replace('*', '%', $limitToUrl);
        $now          = new \DateTime();

        $hasDwellTimeConditions = !empty($action['properties']['accumulative_time']) || !empty($action['properties']['page_hits']);

        // Dwell-time-based conditions (accumulative_time, page_hits) are based on historical data
        // and should be checked even when the current URL doesn't match. This fixes the bug where
        // a contact must revisit the same page for points to be awarded after time threshold is crossed.
        // See https://github.com/mautic/mautic/issues/12336
        if ($hasDwellTimeConditions) {
            $hitStats = $this->hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);

            if (!empty($action['properties']['accumulative_time'])) {
                if (isset($hitStats['sum'])) {
                    $changePoints['accumulative_time'] = $action['properties']['accumulative_time'] <= $hitStats['sum'];
                } else {
                    $changePoints['accumulative_time'] = false;
                }
            }
            if (!empty($action['properties']['page_hits'])) {
                if (isset($hitStats['count'])) {
                    $changePoints['page_hits'] = $hitStats['count'] >= $action['properties']['page_hits'];
                } else {
                    $changePoints['page_hits'] = false;
                }
            }
        }

        // first_time requires the current URL to match (it's about THIS visit being the first)
        // returns_within/returns_after require the current URL to match (they compare with the current hit)
        if ($urlMatches) {
            if (isset($action['properties']['first_time']) && true === $action['properties']['first_time']) {
                if (!isset($hitStats)) {
                    $hitStats = $this->hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
                }
                if (isset($hitStats['count']) && $hitStats['count']) {
                    $changePoints['first_time'] = false;
                } else {
                    $changePoints['first_time'] = true;
                }
            }

            if ($action['properties']['returns_within'] || $action['properties']['returns_after']) {
                $latestHit = $this->hitRepository->getLatestHit(['leadId' => $lead->getId(), 'urls' => [$urlWithSqlWC], 'second_to_last' => $eventDetails->getId()]);
            } else {
                $latestHit = null;
            }

            if ($action['properties']['returns_within']) {
                $changePoints['returns_within'] = $latestHit && $now->getTimestamp() - $latestHit->getTimestamp() <= $action['properties']['returns_within'];
            }
            if ($action['properties']['returns_after']) {
                $changePoints['returns_after'] = $latestHit && $now->getTimestamp() - $latestHit->getTimestamp() >= $action['properties']['returns_after'];
            }
        }

        if ($urlMatches && [] === $changePoints) {
            return true;
        }

        return !in_array(false, $changePoints) && [] !== $changePoints;
    }
}
