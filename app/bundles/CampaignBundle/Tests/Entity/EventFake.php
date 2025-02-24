<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Event;

/**
 * Allows to use the live Event entity and set the ID.
 */
final class EventFake extends Event
{
    private ?int $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
