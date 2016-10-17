<?php

namespace Mautic\ApiBundle\Helper;

use Mautic\ApiBundle\Event\ApiEvent;

class PointEventHelper
{
    public static function validateApiCall(ApiEvent $eventDetails, $action)
    {
        if ((string) $eventDetails->getIdRule() === (string) $action['id']) {
            return true;
        } else {
            return false;
        }
    }
}
