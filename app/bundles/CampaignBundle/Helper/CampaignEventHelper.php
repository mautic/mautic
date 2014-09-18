<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CoreBundle\Factory\MauticFactory;

class CampaignEventHelper
{

    /**
     * Determine if this campaign applies
     *
     * @param CampaignLeadChangeEvent $passthrough
     * @param $event
     *
     * @return bool
     */
    public static function verifyLeadChangeTrigger(CampaignLeadChangeEvent $passthrough, $event)
    {
        $limitToCampaigns = $event['properties']['campaigns'];
        $action           = $event['properties']['action'];

        //check against selected campaigns
        if (!empty($limitToCampaigns) && !in_array($event['campaign']['id'], $limitToCampaigns)) {
            return false;
        }

        //check against the selected action (was lead removed or added)
        $func = 'was' . ucfirst($action);
        if (!$passthrough->$func()) {
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
        $addToCampaigns      = $event['properties']['addTo'];
        $removeFromCampaigns = $event['properties']['removeFrom'];
        $em                  = $factory->getEntityManager();

        if (!empty($addToCampaigns)) {
            foreach ($addToCampaigns as $c) {
                $campaignModel->addLead($em->getReference('MauticCampaignBundle:Campaign', $c), $lead);
            }
        }

        if (!empty($removeFromCampaigns)) {
            foreach ($removeFromCampaigns as $c) {
                $campaignModel->removeLead($em->getReference('MauticCampaignBundle:Campaign', $c), $lead);
            }
        }
    }
}