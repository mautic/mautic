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
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Model\CampaignModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventSubscriber implements EventSubscriberInterface
{
    const LOOPS_TO_FAIL = 100;

    /**
     * @var float
     */
    private $disableCampaignThreshold = 0.35;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    public function __construct(EventRepository $eventRepository, NotificationHelper $notificationHelper, CampaignModel $campaignModel, LeadEventLogRepository $leadEventLogRepository)
    {
        $this->eventRepository        = $eventRepository;
        $this->notificationHelper     = $notificationHelper;
        $this->campaignModel          = $campaignModel;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    /**
     * Get the subscribed events for this listener.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_PRE_SAVE => ['onCampaignPreSave', 0],
            CampaignEvents::ON_EVENT_FAILED   => ['onEventFailed', 0],
            CampaignEvents::ON_EVENT_EXECUTED => ['onEventExecuted', 0],
        ];
    }

    /**
     * Reset all campaign event failed_count's
     * to 0 when the campaign is published.
     */
    public function onCampaignPreSave(CampaignEvent $event)
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

    /**app/bundles/WebhookBundle/Tests/Notificator/WebhookKillNotificatorTest.php
     * Process the FailedEvent event. Notifies users and checks
     * failed thresholds to notify CS and/or disable the campaign.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onEventFailed(FailedEvent $event): void
    {
        $log                  = $event->getLog();
        $failedEvent          = $log->getEvent();
        $campaign             = $failedEvent->getCampaign();
        $lead                 = $log->getLead();
        $countFailedLeadEvent = $this->eventRepository->getFailedCountLeadEvent($lead->getId(), $failedEvent->getId());
        if ($countFailedLeadEvent < self::LOOPS_TO_FAIL) {
            // Do not increase if under LOOPS_TO_FAIL
            return;
        } elseif ($countFailedLeadEvent > self::LOOPS_TO_FAIL &&
            $this->leadEventLogRepository->isLastFailed($lead->getId(), $failedEvent->getId())
        ) {
            // Do not increase twice
            return;
        }
        // Increase if LOOPS_TO_FAIL or last success
        $failedCount   = $this->eventRepository->incrementFailedCount($failedEvent);
        $contactCount  = $campaign->getLeads()->count();
        $failedPercent = $contactCount ? ($failedCount / $contactCount) : 1;

        $this->notificationHelper->notifyOfFailure($lead, $failedEvent);

        if ($failedPercent >= $this->disableCampaignThreshold) {
            $this->notificationHelper->notifyOfUnpublish($failedEvent);
            $campaign->setIsPublished(false);
            $this->campaignModel->saveEntity($campaign);
        }
    }

    /**
     * Check the fail log if the lead is recorded there. If yes it decrease the failed count. It prevents counting
     * the same failure twice.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onEventExecuted(ExecutedEvent $event): void
    {
        $log                  = $event->getLog();
        $executedEvent        = $log->getEvent();
        $lead                 = $log->getLead();
        $countFailedLeadEvent = $this->eventRepository->getFailedCountLeadEvent($lead->getId(), $executedEvent->getId());
        // Decrease if success event and last failed
        if (!$this->leadEventLogRepository->isLastFailed($lead->getId(), $executedEvent->getId()) ||
            $countFailedLeadEvent < self::LOOPS_TO_FAIL
        ) {
            // Do not decrease if under LOOPS_TO_FAIL or last succes
            return;
        }
        // Decrease if last failed and over the LOOPS_TO_FAIL
        $this->eventRepository->decreaseFailedCount($executedEvent);
    }
}
