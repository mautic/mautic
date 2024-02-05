<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;

final class DeleteCampaign extends \Symfony\Contracts\EventDispatcher\Event
{
    private Campaign $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
