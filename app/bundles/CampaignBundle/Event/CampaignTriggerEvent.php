<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

final class CampaignTriggerEvent extends Event
{
    private bool $triggerCampaign = true;

    public function __construct(
        private readonly Campaign $campaign,
    ) {
    }

    /**
     * Returns the Campaign entity.
     */
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function shouldTrigger(): bool
    {
        return $this->triggerCampaign;
    }

    /**
     * Do not trigger this campaign.
     */
    public function doNotTrigger(): void
    {
        $this->triggerCampaign = false;

        $this->stopPropagation();
    }
}
