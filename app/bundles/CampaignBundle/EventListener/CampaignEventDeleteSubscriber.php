<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteCampaign;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Helper\CampaignConfig;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;

final readonly class CampaignEventDeleteSubscriber
{
    public function __construct(
        private LeadEventLogRepository $leadEventLogRepository,
        private CampaignConfig $campaignConfig,
        private CampaignModel $campaignModel,
        private EventModel $eventModel,
    ) {
    }

    #[AsEventListener(event: DeleteCampaign::class)]
    public function onCampaignDelete(DeleteCampaign $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }

        $campaignId = $event->getCampaign()->getId();
        // For campaign deletion, remove both events and logs
        $this->leadEventLogRepository->removeEventLogsByCampaignId($campaignId);
        $this->eventModel->deleteEventsByCampaignId($campaignId);
        $this->campaignModel->deleteCampaign($event->getCampaign());
    }

    #[AsEventListener(event: DeleteEvent::class)]
    public function onEventDelete(DeleteEvent $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }
        $eventIds = $event->getEventIds();
        // For individual event deletion, only soft-delete events but keep the logs
        $this->eventModel->deleteEventsByEventIds($eventIds);
    }
}
