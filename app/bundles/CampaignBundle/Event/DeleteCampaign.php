<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;

final class DeleteCampaign extends \Symfony\Contracts\EventDispatcher\Event
{
    public function __construct(private Campaign $campaign)
    {
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
