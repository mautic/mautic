<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PointActionHelper
 */
class PointActionHelper
{

    /**
     * @param MauticFactory $factory
     * @param               $eventDetails
     * @param               $action
     *
     * @return bool
     */
    public static function validatePageHit($factory, $eventDetails, $action)
    {
        $pageHit = $eventDetails->getPage();

        if ($pageHit instanceof Page) {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel = $factory->getModel('page');
            list($parent, $children)  = $pageModel->getVariants($pageHit);
            //use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        $limitToPages = $action['properties']['pages'];

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            //no points change
            return false;
        }

        return true;
    }

    /**
     * @param MauticFactory $factory
     * @param               $eventDetails
     * @param               $action
     *
     * @return bool
     */
    public static function validateUrlHit($factory, $eventDetails, $action)
    {
        $changePoints   = array();
        $url            = $eventDetails->getUrl();
        $limitToUrl     = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl || !fnmatch($limitToUrl, $url)) {
            //no points change
            return false;
        }

        $hitRepository  = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $lead           = $eventDetails->getLead();
        $urlWithSqlWC   = str_replace('*', '%', $url);

        if (isset($action['properties']['first_time']) && $action['properties']['first_time'] === true) {
            $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            if (isset($hitStats['count']) && $hitStats['count']) {
                $changePoints['first_time'] = false;
            } else {
                $changePoints['first_time'] = true;
            }
        }

        if ($action['properties']['accumulative_time']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }
            if (isset($hitStats['sum']) && $hitStats['sum'] >= $action['properties']['accumulative_time']) {
                $changePoints['accumulative_time'] = true;
            } else {
                $changePoints['accumulative_time'] = false;
            }
        }

        if ($action['properties']['page_hits']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }
            if (isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits']) {
                $changePoints['page_hits'] = true;
            } else {
                $changePoints['page_hits'] = false;
            }
        }

        if ($action['properties']['returns_within']) {
            $latestHit = $hitRepository->getLatestHit(['leadId' => $lead->getId(), $urlWithSqlWC]);
            $latestPlus = clone $latestHit;
            $latestPlus->add(new \DateInterval('PT' . $action['properties']['returns_within'] . 'S'));
            $now = new \dateTime();
            if ($latestPlus >= $now) {
                $changePoints['returns_within'] = true;
            } else {
                $changePoints['returns_within'] = false;
            }
        }

        if ($action['properties']['returns_after']) {
            if (!isset($latestHit)) {
                $latestHit = $hitRepository->getLatestHit(['leadId' => $lead->getId(), $urlWithSqlWC]);
            }
            $latestPlus = clone $latestHit;
            $latestPlus->add(new \DateInterval('PT' . $action['properties']['returns_after'] . 'S'));
            $now = new \dateTime();
            if ($latestPlus >= $now) {
                $changePoints['returns_after'] = true;
            } else {
                $changePoints['returns_after'] = false;
            }
        }

        // return true only if all configured options are true
        return !in_array(false, $changePoints);
    }
}
