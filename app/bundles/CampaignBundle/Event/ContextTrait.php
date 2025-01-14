<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;

trait ContextTrait
{
    /**
     * Check if an event is applicable.
     *
     * @param $eventType
     *
     * @return bool
     */
    public function checkContext($eventType)
    {
        if (!$this->event) {
            return false;
        }

        $type = ($this->event instanceof Event) ? $this->event->getType() : $this->event['type'];

        return strtolower($eventType) === strtolower($type);
    }
}
