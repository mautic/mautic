<?php

namespace Mautic\LeadBundle\Helper;

/**
 * Class PointEventHelper.
 */
class PointEventHelper
{
    /**
     * @param $event
     * @param $factory
     * @param $lead
     *
     * @return bool
     */
    public static function changeLists($event, $factory, $lead)
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
