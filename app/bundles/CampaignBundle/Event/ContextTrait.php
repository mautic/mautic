<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;

trait ContextTrait
{
    abstract public function getEvent(): Event;

    /**
     * Check if an event is applicable.
     */
    public function checkContext($eventType): bool
    {
        return strtolower((string) $eventType) === strtolower((string) $this->getEvent()->getType());
    }
}
