<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

final class DeleteCampaign extends Event
{
    public function __construct(
        private readonly Campaign $campaign,
    ) {
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
