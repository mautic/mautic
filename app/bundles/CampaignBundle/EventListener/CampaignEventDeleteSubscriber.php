<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Helper\CampaignConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignConfig
     */
    private $campaignConfig;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    public function __construct(LeadEventLogRepository $leadEventLogRepository, CampaignConfig $campaignConfig)
    {
        $this->campaignConfig             = $campaignConfig;
        $this->leadEventLogRepository     = $leadEventLogRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::ON_EVENT_DELETE => ['onEventDelete', 0],
        ];
    }

    public function onEventDelete(DeleteEvent $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }
        $eventIds = $event->getEventIds();
        if (empty($eventIds)) {
            return;
        }

        $this->leadEventLogRepository->removeEventLogs($eventIds);
    }
}
