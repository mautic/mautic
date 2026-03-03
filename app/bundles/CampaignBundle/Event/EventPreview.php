<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\DTO\EventPreviewStatDto;
use Mautic\CampaignBundle\Entity\Event;

final class EventPreview
{
    /** @var array<string, EventPreviewStatDto> */
    public array $eventStats = [];

    public function __construct(public Event $event)
    {
    }

    public function isType(string $type): bool
    {
        return $this->event->getType() === $type;
    }

    public function isCampaignRestartAllowed(): bool
    {
        return $this->event->getCampaign()->getAllowRestart();
    }

    public function addEventStat(string $key, mixed $value, ?string $tooltip = null): void
    {
        $this->eventStats[$key] = new EventPreviewStatDto($value, $tooltip);
    }
}
