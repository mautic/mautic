<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CoreBundle\Factory\MauticFactory;

class CampaignEventHelper
{
    /**
     * Determine if this campaign applies.
     *
     * @param CampaignLeadChangeEvent $eventDetails
     * @param array                   $event
     *
     * @return bool
     */
    public static function validateLeadChangeTrigger(CampaignLeadChangeEvent $eventDetails = null, array $event)
    {
        if ($eventDetails == null) {
            return true;
        }

        $limitToCampaigns = $event['properties']['campaigns'];
        $action           = $event['properties']['action'];

        //check against selected campaigns
        if (!empty($limitToCampaigns) && !in_array($event['campaign']['id'], $limitToCampaigns)) {
            return false;
        }

        //check against the selected action (was lead removed or added)
        $func = 'was'.ucfirst($action);
        if (!method_exists($eventDetails, $func) || !$eventDetails->$func()) {
            return false;
        }

        return true;
    }

    /**
     * @param MauticFactory $factory
     * @param               $lead
     * @param               $event
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public static function addRemoveLead(MauticFactory $factory, $lead, $event)
    {
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel       = $factory->getModel('campaign');
        $properties          = $event['properties'];
        $addToCampaigns      = $properties['addTo'];
        $removeFromCampaigns = $properties['removeFrom'];
        $em                  = $factory->getEntityManager();
        $leadsModified       = false;

        if (!empty($addToCampaigns)) {
            foreach ($addToCampaigns as $c) {
                $campaignModel->addLead($em->getReference('MauticCampaignBundle:Campaign', $c), $lead, true);
            }
            $leadsModified = true;
        }

        if (!empty($removeFromCampaigns)) {
            foreach ($removeFromCampaigns as $c) {
                if ($c == 'this') {
                    $c = $event['campaign']['id'];
                }

                $campaignModel->removeLead($em->getReference('MauticCampaignBundle:Campaign', $c), $lead, true);
            }
            $leadsModified = true;
        }

        return $leadsModified;
    }
}
