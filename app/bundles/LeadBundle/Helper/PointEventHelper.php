<?php

namespace Mautic\LeadBundle\Helper;

/**
 * @deprecated It will be removed in Mautic 6.0
 */
class PointEventHelper
{
    public static function changeLists($event, $factory, $lead): bool
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
}
