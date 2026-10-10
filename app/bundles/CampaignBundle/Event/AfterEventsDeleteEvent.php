<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class AfterEventsDeleteEvent extends Event
{
    /**
     * @param string[] $eventIds
     */
    public function __construct(
        private readonly array $eventIds,
    ) {
    }

    /**
     * @return string[]
     */
    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
