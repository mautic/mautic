<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\LeadBundle\Event\ListChangeEvent;

/**
 * Class CampaignEventHelper
 *
 * @package Mautic\LeadBundle\Helper
 */
class CampaignEventHelper
{
    /**
     * @param $event
     * @param $factory
     * @param $lead
     *
     * @return bool
     */
    public static function changeLists ($event, $factory, $lead)
    {
        $properties = $event['properties'];

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        $somethingHappened = false;

        if (!empty($addTo)) {
            $leadModel->addToLists($lead, $addTo);
            $somethingHappened = true;
        }

        if (!empty($removeFrom)) {
            $leadModel->removeFromLists($lead, $removeFrom);
            $somethingHappened = true;
        }

        return $somethingHappened;
    }

    /**
     * @param               $event
     * @param               $lead
     * @param MauticFactory $factory
     *
     * @return bool
     */
    public static function changePoints ($event, $lead, MauticFactory $factory)
    {
        $points = $event['properties']['points'];

        $somethingHappened = false;

        if ($lead != null && !empty($points)) {
            $lead->addToPoints($points);

            //add a lead point change log
            $log = new PointsChangeLog();
            $log->setDelta($points);
            $log->setLead($lead);
            $log->setType('campaign');
            $log->setEventName("{$event['campaign']['id']}: {$event['campaign']['name']}");
            $log->setActionName("{$event['id']}: {$event['name']}");
            $log->setIpAddress($factory->getIpAddress());
            $log->setDateAdded(new \DateTime());
            $lead->addPointsChangeLog($log);

            $factory->getModel('lead')->saveEntity($lead);
            $somethingHappened = true;
        }

        return $somethingHappened;
    }

    /**
     * @param $event
     * @param $factory
     * @param $lead
     *
     * @return bool
     */
    public static function updateLead ($event, $factory, $lead)
    {
        $properties = $event['properties'];

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $leadModel->setFieldValues($lead, $properties, false);
        $leadModel->saveEntity($lead);

        return true;
    }

    /**
     * @param      $event
     * @param Lead $lead
     *
     * @return bool
     */
    public static function validatePointChange ($event, Lead $lead)
    {
        $properties  = $event['properties'];
        $checkPoints = $properties['points'];

        if (!empty($checkPoints)) {
            $points = $lead->getPoints();
            if ($points < $checkPoints) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ListChangeEvent $eventDetails
     * @param                 $event
     *
     * @return bool
     */
    public static function validateListChange (ListChangeEvent $eventDetails, $event)
    {
        $limitAddTo      = $event['properties']['addedTo'];
        $limitRemoveFrom = $event['properties']['removedFrom'];
        $list            = $eventDetails->getList();

        if ($eventDetails->wasAdded() && !empty($limitAddTo) && !in_array($list->getId(), $limitAddTo)) {
            return false;
        }

        if ($eventDetails->wasRemoved() && !empty($limitRemoveFrom) && !in_array($list->getId(), $limitRemoveFrom)) {
            return false;
        }

        return true;
    }
}