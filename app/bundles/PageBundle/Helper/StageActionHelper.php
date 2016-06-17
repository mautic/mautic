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
class StageActionHelper
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
        $url            = $eventDetails->getUrl();
        $limitToUrl     = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl || !fnmatch($limitToUrl, $url)) {
            //no points change
            return false;
        }

        $hitRepository  = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $lead           = $eventDetails->getLead();

        if ($action['properties']['first_time'] === true) {
            $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            if (isset($hitStats['count']) && $hitStats['count']) {
                $changeStage['first_time'] = false;
            } else {
                $changeStage['first_time'] = true;
            }
        }

        if ($action['properties']['accumulative_time']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            if (isset($hitStats['sum']) && $hitStats['sum'] >= $action['properties']['accumulative_time']) {
                $changeStage['accumulative_time'] = true;
            } else {
                $changeStage['accumulative_time'] = false;
            }
        }

        if ($action['properties']['page_hits']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            if (isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits']) {
                $changeStage['page_hits'] = true;
            } else {
                $changeStage['page_hits'] = false;
            }
        }

        if ($action['properties']['returns_within']) {
            $latestHit = $hitRepository->getLatestHit(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            $latestPlus = clone $latestHit;
            $latestPlus->add(new \DateInterval('PT' . $action['properties']['returns_within'] . 'S'));
            $now = new \dateTime();
            if ($latestPlus >= $now) {
                $changeStage['returns_within'] = true;
            } else {
                $changeStage['returns_within'] = false;
            }
        }

        if ($action['properties']['returns_after']) {
            if (!isset($latestHit)) {
                $latestHit = $hitRepository->getLatestHit(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            $latestPlus = clone $latestHit;
            $latestPlus->add(new \DateInterval('PT' . $action['properties']['returns_after'] . 'S'));
            $now = new \dateTime();
            if ($latestPlus >= $now) {
                $changeStage['returns_after'] = true;
            } else {
                $changeStage['returns_after'] = false;
            }
        }

        // return true only if all configured options are true
        return !in_array(false, $changeStage);
    }
}
