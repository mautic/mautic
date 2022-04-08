<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\FailedLeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventLogCleanupSubscriber implements EventSubscriberInterface
{
    private FailedLeadEventLogRepository $failedLeadEventLogRepository;

    public function __construct(FailedLeadEventLogRepository $failedLeadEventLogRepository)
    {
        $this->failedLeadEventLogRepository = $failedLeadEventLogRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::ON_EVENT_EXECUTED_BATCH => ['onEventBatchExecuted', -100],
        ];
    }

    /**
     * Deletes failed log entries for all successful event logs.
     */
    public function onEventBatchExecuted(ExecutedBatchEvent $event): void
    {
        $ids = $event->getExecuted()
            ->map(fn (LeadEventLog $eventLog) => $eventLog->getId())
            ->getValues();

        if (!$ids) {
            return;
        }

        $this->failedLeadEventLogRepository->deleteByIds($ids);
    }
}
