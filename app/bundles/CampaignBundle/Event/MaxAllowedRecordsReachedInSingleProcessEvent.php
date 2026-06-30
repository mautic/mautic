<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MaxAllowedRecordsReachedInSingleProcessEvent extends Event
{
    public function __construct(private readonly int $campaignId)
    {
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }
}
