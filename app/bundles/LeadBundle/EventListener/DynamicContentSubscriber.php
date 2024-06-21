<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DynamicContentSubscriber implements EventSubscriberInterface
{
    public function __construct(private LeadListRepository $segmentRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['onContactFilterEvaluate', 0],
        ];
    }

    public function onContactFilterEvaluate(ContactFiltersEvaluateEvent $event): void
    {
        foreach ($event->getFilters() as $filter) {
            if ('leadlist' === $filter['type']) {
                // Segment membership evaluation. Check if contact/segment relationship is correct.
                $event->setIsMatched(
                    $this->isContactSegmentRelationshipValid($event->getContact(), $filter['operator'], $filter['filter'])
                );
                $event->setIsEvaluated(true);

                return;
            }
        }
    }

    /**
     * @param string $operator   empty, !empty, in, !in
     * @param ?int[] $segmentIds
     */
    private function isContactSegmentRelationshipValid(Lead $contact, string $operator, array $segmentIds = null): bool
    {
        $contactId = (int) $contact->getId();

        return match ($operator) {
            OperatorOptions::EMPTY     => $this->segmentRepository->isNotContactInAnySegment($contactId), // Contact is not in any segment
            OperatorOptions::NOT_EMPTY => $this->segmentRepository->isContactInAnySegment($contactId), // Contact is in any segment
            OperatorOptions::IN        => $this->segmentRepository->isContactInSegments($contactId, $segmentIds), // Contact is in one of the segment provided in $segmentsIds
            OperatorOptions::NOT_IN    => $this->segmentRepository->isNotContactInSegments($contactId, $segmentIds), // Contact is not in all segments provided in $segmentsIds
            default                    => throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator)),
        };
    }
}
