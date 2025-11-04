<?php

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\Exceptions\CampaignAlreadyUnpublishedException;
use Mautic\CampaignBundle\Model\Exceptions\CampaignVersionMismatchedException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventSubscriber implements EventSubscriberInterface
{
    public const LOOPS_TO_FAIL = 100;

    private const MINIMUM_CONTACTS_FOR_DISABLE = 100;
    private const DISABLE_CAMPAIGN_THRESHOLD   = 0.35;

    public function __construct(
        private EventRepository $eventRepository,
        private CampaignModel $campaignModel,
        private LeadEventLogRepository $leadEventLogRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Get the subscribed events for this listener.
     *
     * @return array<string,mixed>
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
     *
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function onEventFailed(FailedEvent $event): void
    {
        $log                  = $event->getLog();
        $failedEvent          = $log->getEvent();
        $campaign             = $failedEvent->getCampaign();
        $lead                 = $log->getLead();
        $countFailedLeadEvent = $this->eventRepository->getFailedCountLeadEvent($lead->getId(), $failedEvent->getId());

        // Do not increase if under LOOPS_TO_FAIL || Do not increase twice
        if (($countFailedLeadEvent < self::LOOPS_TO_FAIL) || ($countFailedLeadEvent > self::LOOPS_TO_FAIL
                && $this->leadEventLogRepository->isLastFailed($lead->getId(), $failedEvent->getId())
        )) {
            return;
        }
        // Increase if LOOPS_TO_FAIL or last success
        $failedCount   = $this->eventRepository->incrementFailedCount($failedEvent);
        $contactCount  = $campaign->getLeads()->count();
        $failedPercent = $contactCount ? ($failedCount / $contactCount) : 1;

        if ($this->eventDispatcher->hasListeners(CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY)) {
            $this->eventDispatcher->dispatch(
                new NotifyOfFailureEvent($lead, $failedEvent),
                CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY
            );
        }

        if ($contactCount >= self::MINIMUM_CONTACTS_FOR_DISABLE
            && $failedPercent >= self::DISABLE_CAMPAIGN_THRESHOLD
            // Trigger only if published, if unpublished, do not trigger further notifications
            && $campaign->isPublished()) {
            try {
                $this->campaignModel->transactionalCampaignUnPublish($campaign);
            } catch (CampaignAlreadyUnpublishedException|CampaignVersionMismatchedException) {
                return;
            }

            if ($this->eventDispatcher->hasListeners(CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY)) {
                $this->eventDispatcher->dispatch(
                    new NotifyOfUnpublishEvent($failedEvent),
                    CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY
                );
            }
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
        $leadId               = ($lead->getId() > 0) ? $lead->getId() : $lead->deletedId;

        $countFailedLeadEvent = $this->eventRepository->getFailedCountLeadEvent($leadId, $executedEvent->getId());
        // Decrease if success event and last failed
        if (!$this->leadEventLogRepository->isLastFailed($leadId, $executedEvent->getId())
            || $countFailedLeadEvent < self::LOOPS_TO_FAIL
        ) {
            // Do not decrease if under LOOPS_TO_FAIL or last success
            return;
        }
        // Decrease if last failed and over the LOOPS_TO_FAIL
        $this->eventRepository->decreaseFailedCount($executedEvent);
    }
}
