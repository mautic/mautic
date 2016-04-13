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
    
    
    private static function log($message){
        $fp = fopen('data.txt', 'a+');
        fwrite($fp, $message." \n\r");
        fclose($fp);
    }

    /**
     * @param MauticFactory $factory
     * @param               $eventDetails
     * @param               $action
     *
     * @return bool
     */
    public static function validatePageHit($factory, $eventDetails, $action)
    {
        
        self::log("validatePageHit :: ".print_r($action, true));
        
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
        self::log("validateUrlHit :: ".var_dump($action));
        
        $changePoints   = array();
        $url            = $eventDetails->getUrl();
        $limitToUrl     = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl || !fnmatch($limitToUrl, $url)) {
            //no points change
            return false;
        }

        $hitRepository  = $factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        $lead           = $eventDetails->getLead();
        
        self::log("action properties :: ".print_r($action['properties'], true));

        if (isset($action['properties']['first_time']) && $action['properties']['first_time'] === true) {
            
            self::log("validateUrlHit :: fisttime :: ".$action['id']);
            
            $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            if (isset($hitStats['count']) && $hitStats['count']) {
                $changePoints['first_time'] = false;
            } else {
                $changePoints['first_time'] = true;
            }
        }

        if ($action['properties']['accumulative_time']) {
            
            self::log("validateUrlHit :: accumulative_time :: ".$action['id']);
            
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            if (isset($hitStats['sum']) && $hitStats['sum'] >= $action['properties']['accumulative_time']) {
                $changePoints['accumulative_time'] = true;
            } else {
                $changePoints['accumulative_time'] = false;
            }
        }

        if ($action['properties']['page_hits']) {
            
            self::log("validateUrlHit :: page_hits :: ".$action['id']);
            
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimes(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            if (isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits']) {
                $changePoints['page_hits'] = true;
            } else {
                $changePoints['page_hits'] = false;
            }
        }

        if ($action['properties']['returns_within']) {
            
            self::log("validateUrlHit :: returns_within :: ".$action['id']);
            
            $latestHit = $hitRepository->getLatestHit(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
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
            
            self::log("validateUrlHit :: returns_after :: ".$action['id']. " :: ".$action['properties']['returns_after']);
            
            if (!isset($latestHit)) {
                self::log("validateUrlHit :: return_after 1 :: initatilisation de latestHit");
                $latestHit = $hitRepository->getLatestHit(array('leadId' => $lead->getId(), 'urls' => str_replace('*', '%', $url)));
            }
            $latestPlus = clone $latestHit; // time 
            $latestPlus->add(new \DateInterval('PT' . $action['properties']['returns_after'] . 'S'));
            $now = new \dateTime();
            
            self::log("validateUrlHit :: return_after latest :: ". date_format($latestHit, "c"));
            self::log("validateUrlHit :: return_after latestPlus :: ".date_format($latestPlus, "c"));
            self::log("validateUrlHit :: return_after now :: ".date_format($now, "c"));
            
           // if ($latestPlus >= $now) { // DE BASE
            if ($latestPlus <= $now) { 
                self::log("validateUrlHit :: return_after 2 :: latestHit >= now returning TRUE");
                $changePoints['returns_after'] = true;
            } else {
                self::log("validateUrlHit :: return_after 3 :: latestHit != now returning FALSE");
                $changePoints['returns_after'] = false;
            }
        }

        self::log("validateUrlHit :: changepoint :: ".var_dump($changePoints));
        
        self::log("validateUrlHit :: in array ".in_array(false, $changePoints));
        
        // return true only if all configured options are true
        return !in_array(false, $changePoints);
    }
}
