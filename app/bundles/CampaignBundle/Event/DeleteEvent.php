<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

final class DeleteEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var string[]
     */
    private array $eventIds;

    /**
     * @param string[] $eventIds
     */
    public function __construct(array $eventIds)
    {
        $this->eventIds = $eventIds;
    }

    /**
     * @return string[]
     */
    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
