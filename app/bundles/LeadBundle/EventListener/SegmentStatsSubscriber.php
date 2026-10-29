<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Event\GetStatDataEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class SegmentStatsSubscriber
{
    public function __construct(
        private LeadListRepository $leadListRepository,
    ) {
    }

    #[AsEventListener(
        event: GetStatDataEvent::class,
        priority: 0
    )]
    public function getStatsLeadEvents(GetStatDataEvent $event): void
    {
        $result = array_merge(
            $this->leadListRepository->getCampaignEntryPoints(),
            $this->leadListRepository->getEmailIncludeExcludeList(),
            $this->leadListRepository->getCampaignChangeSegmentAction(),
            $this->leadListRepository->getFilterSegmentsAction(),
            $this->leadListRepository->getLeadListLeads(),
            $this->leadListRepository->getNotificationIncludedList(),
            $this->leadListRepository->getSMSIncludedList(),
            $this->leadListRepository->getFormAction()
        );

        $allSegments = $this->leadListRepository->getAllSegments();

        $stats = array_map(function (array $data) use ($result): array {
            if (in_array($data['item_id'], array_column($result, 'item_id'))) {
                $data['is_used'] = 1;
            }

            return $data;
        }, $allSegments);

        $event->addResult($stats);
    }
}
