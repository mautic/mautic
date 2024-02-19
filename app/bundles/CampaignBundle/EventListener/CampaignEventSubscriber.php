<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

class CampaignEventSubscriber extends CommonSubscriber
{
    /**
     * @var float
     */
    private $disableCampaignThreshold = 0.1;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @param EventRepository    $eventRepository
     * @param NotificationHelper $notificationHelper
     */
    public function __construct(EventRepository $eventRepository, NotificationHelper $notificationHelper)
    {
        $this->eventRepository    = $eventRepository;
        $this->notificationHelper = $notificationHelper;
    }

    /**
     * Get the subscribed events for this listener.
     *
     * @return array
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
     *
     * @param CampaignEvent $event
     */
    public function onCampaignPreSave(CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $changes  = $campaign->getChanges();

        if (array_key_exists('isPublished', $changes)) {
            list($actual, $inMemory) = $changes['isPublished'];

            // If we're publishing the campaign
            if ($actual === false && $inMemory === true) {
                $this->eventRepository->resetFailedCountsForEventsInCampaign($campaign);
            }
        }
    }

    /**
     * Process the FailedEvent event. Notifies users and checks
     * failed thresholds to notify CS and/or disable the campaign.
     *
     * @param FailedEvent $event
     */
    public function onEventFailed(FailedEvent $event)
    {
        $log           = $event->getLog();
        $failedEvent   = $log->getEvent();
        $campaign      = $failedEvent->getCampaign();
        $failedCount   = $this->eventRepository->incrementFailedCount($failedEvent);
        $contactCount  = $campaign->getLeads()->count();
        $failedPercent = $contactCount ? ($failedCount / $contactCount) : 1;

        $this->notificationHelper->notifyOfFailure($log->getLead(), $failedEvent);

        if ($failedPercent >= $this->disableCampaignThreshold) {
            $this->notificationHelper->notifyOfUnpublish($failedEvent);
            $campaign->setIsPublished(false);
            $this->em->persist($campaign);
        }
    }
}
