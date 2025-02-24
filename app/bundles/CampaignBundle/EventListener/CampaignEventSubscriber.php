<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventSubscriber implements EventSubscriberInterface
{
    private float $disableCampaignThreshold = 0.1;

    public function __construct(private EventRepository $eventRepository, private NotificationHelper $notificationHelper, private CampaignRepository $campaignRepository)
    {
    }

    /**
     * Get the subscribed events for this listener.
     *
     * @return array<string,mixed>
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_PRE_SAVE => ['onCampaignPreSave', 0],
            CampaignEvents::ON_EVENT_FAILED   => ['onEventFailed', 0],
        ];
    }

    /**
     * Reset all campaign event failed_count's
     * to 0 when the campaign is published.
     */
    public function onCampaignPreSave(CampaignEvent $event): void
    {
        $campaign = $event->getCampaign();
        $changes  = $campaign->getChanges();

        if (array_key_exists('isPublished', $changes)) {
            list($actual, $inMemory) = $changes['isPublished'];

            // If we're publishing the campaign
            if (false === $actual && true === $inMemory) {
                $this->eventRepository->resetFailedCountsForEventsInCampaign($campaign);
            }
        }
    }

    /**
     * Process the FailedEvent event. Notifies users and checks
     * failed thresholds to notify CS and/or disable the campaign.
     */
    public function onEventFailed(FailedEvent $event): void
    {
        $log           = $event->getLog();
        $failedEvent   = $log->getEvent();
        $campaign      = $failedEvent->getCampaign();
        $failedCount   = $this->eventRepository->incrementFailedCount($failedEvent);
        $contactCount  = $campaign->getLeads()->count();
        $failedPercent = $contactCount ? ($failedCount / $contactCount) : 1;

        $this->notificationHelper->notifyOfFailure($log->getLead(), $failedEvent);

        if ($failedPercent >= $this->disableCampaignThreshold && $campaign->isPublished()) {
            $this->notificationHelper->notifyOfUnpublish($failedEvent);
            $campaign->setIsPublished(false);
            $this->campaignRepository->saveEntity($campaign);
        }
    }
}
