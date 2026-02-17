<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteCampaign;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Helper\CampaignConfig;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadEventLogRepository $leadEventLogRepository,
        private CampaignConfig $campaignConfig,
        private CampaignModel $campaignModel,
        private EventModel $eventModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::ON_CAMPAIGN_DELETE => ['onCampaignDelete', 0],
            CampaignEvents::ON_EVENT_DELETE    => ['onEventDelete', 0],
        ];
    }

    public function onCampaignDelete(DeleteCampaign $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }

        $campaignId = $event->getCampaign()->getId();
        $this->leadEventLogRepository->removeEventLogsByCampaignId($campaignId);
        $this->eventModel->deleteEventsByCampaignId($campaignId);
        $this->campaignModel->deleteCampaign($event->getCampaign());
    }

    public function onEventDelete(DeleteEvent $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }
        $eventIds   = $event->getEventIds();
        $this->leadEventLogRepository->removeEventLogs($eventIds);
        $this->eventModel->deleteEventsByEventIds($eventIds);
    }
}
