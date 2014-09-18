<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

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
     */
    public static function changeLists ($event, $factory)
    {
        $properties = $event['properties'];

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $lead       = $leadModel->getCurrentLead();
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
}