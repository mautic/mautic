<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Entity\FailedLeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ExecutedBatchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CampaignEventLogCleanupSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FailedLeadEventLogRepository $failedLeadEventLogRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExecutedBatchEvent::class => ['onEventBatchExecuted', -100],
        ];
    }

    /**
     * Deletes failed log entries for all successful event logs.
     */
    public function onEventBatchExecuted(ExecutedBatchEvent $event): void
    {
        $ids = $event->getExecuted()
            ->map(fn (LeadEventLog $eventLog): int => $eventLog->getId())
            ->getValues();

        if (!$ids) {
            return;
        }

        $this->failedLeadEventLogRepository->deleteByIds($ids);
    }
}
