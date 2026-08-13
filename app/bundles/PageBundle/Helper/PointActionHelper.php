<?php

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Helper\UrlMatcher;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;

class PointActionHelper
{
    /**
     * @param MauticFactory $factory
     */
    public static function validatePageHit($factory, $eventDetails, $action): bool
    {
        $pageHit = $eventDetails->getPage();

        if ($pageHit instanceof Page) {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel               = $factory->getModel('page');
            [$parent, $children]     = $pageHit->getVariants();
            // use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        // If no pages are selected, the pages array does not exist
        if (isset($action['properties']['pages'])) {
            $limitToPages = $action['properties']['pages'];
        }

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            // no points change
            return false;
        }

        return true;
    }

    /**
     * @param MauticFactory $factory
     */
    public static function validateUrlHit($factory, $eventDetails, $action): bool
    {
        $changePoints = [];
        $url          = $eventDetails->getUrl();
        $limitToUrl   = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl) {
            return false;
        }

        $urlMatches     = UrlMatcher::hasMatch([$limitToUrl], $url);
        $hitRepository  = $factory->getEntityManager()->getRepository(Hit::class);
        $lead           = $eventDetails->getLead();
        $urlWithSqlWC   = self::getSqlLikePattern($limitToUrl);
        $now            = new \DateTime();

        $hasDwellTimeConditions = !empty($action['properties']['accumulative_time']) || !empty($action['properties']['page_hits']);

        // Dwell-time-based conditions (accumulative_time, page_hits) are based on historical data
        // and should be checked even when the current URL doesn't match. This fixes the bug where
        // a contact must revisit the same page for points to be awarded after time threshold is crossed.
        // See https://github.com/mautic/mautic/issues/12336
        if ($hasDwellTimeConditions) {
            $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);

            if (!empty($action['properties']['accumulative_time'])) {
                $changePoints['accumulative_time'] = isset($hitStats['sum']) && $action['properties']['accumulative_time'] <= $hitStats['sum'];
            }
            if (!empty($action['properties']['page_hits'])) {
                $changePoints['page_hits'] = isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits'];
            }
        }

        // first_time requires the current URL to match (it's about THIS visit being the first)
        // returns_within/returns_after require the current URL to match (they compare with the current hit)
        if ($urlMatches) {
            if (isset($action['properties']['first_time']) && true === $action['properties']['first_time']) {
                if (!isset($hitStats)) {
                    $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
                }
                $changePoints['first_time'] = empty($hitStats['count']);
            }

            if ($action['properties']['returns_within'] || $action['properties']['returns_after']) {
                $latestHit = $hitRepository->getLatestHit(['leadId' => $lead->getId(), 'urls' => [$urlWithSqlWC], 'second_to_last' => $eventDetails->getId()]);
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

    private static function getSqlLikePattern(string $url): string
    {
        $url = UrlMatcher::normalizeUrl($url);
        $url = addcslashes($url, '\\_%');

        if (false !== strpbrk($url, '*?')) {
            return str_replace(['*', '?'], ['%', '_'], $url);
        }

        return '%'.$url.'%';
    }
}
