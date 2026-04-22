<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\SegmentCompanyRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DynamicContentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadListRepository $segmentRepository,
        private SegmentCompanyRepository $companySegmentRepository,
    ) {
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

            if ('company_segments' === $filter['type']) {
                $event->setIsMatched(
                    $this->isContactPrimaryCompanySegmentRelationshipValid($event->getContact(), $filter['operator'], $filter['filter'])
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
    private function isContactSegmentRelationshipValid(Lead $contact, string $operator, ?array $segmentIds = null): bool
    {
        $contactId = (int) $contact->getId();

        return match ($operator) {
            OperatorOptions::EMPTY         => $this->segmentRepository->isNotContactInAnySegment($contactId), // Contact is not in any segment
            OperatorOptions::NOT_EMPTY     => $this->segmentRepository->isContactInAnySegment($contactId), // Contact is in any segment
            OperatorOptions::INCLUDING_ANY => $this->segmentRepository->isContactInSegments($contactId, $segmentIds), // Contact is in one of the segment provided in $segmentsIds
            OperatorOptions::EXCLUDING_ANY => $this->segmentRepository->isNotContactInSegments($contactId, $segmentIds), // Contact is not in some segments provided in $segmentsIds
            OperatorOptions::INCLUDING_ALL => $this->segmentRepository->isContactInAllSegments($contactId, $segmentIds), // Contact is in all segments provided in $segmentsIds
            OperatorOptions::EXCLUDING_ALL => $this->segmentRepository->isNotContactInAllSegments($contactId, $segmentIds), // Contact is not in all segments provided in $segmentsIds
            default                        => throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator)),
        };
    }

    /**
     * @param string $operator   empty, !empty, in, !in
     * @param ?int[] $segmentIds
     */
    private function isContactPrimaryCompanySegmentRelationshipValid(Lead $contact, string $operator, ?array $segmentIds = null): bool
    {
        $contactId = (int) $contact->getId();

        return match ($operator) {
            OperatorOptions::EMPTY         => $this->companySegmentRepository->isNotContactPrimaryCompanyInAnySegment($contactId), // Contact's primary company is not in any segment
            OperatorOptions::NOT_EMPTY     => $this->companySegmentRepository->isContactPrimaryCompanyInAnySegment($contactId), // Contact's primary company is in any segment
            OperatorOptions::INCLUDING_ANY => $this->companySegmentRepository->isContactPrimaryCompanyInSegments($contactId, $segmentIds), // Contact's primary company is in one of the segments provided
            OperatorOptions::EXCLUDING_ANY => $this->companySegmentRepository->isNotContactPrimaryCompanyInSegments($contactId, $segmentIds), // Contact's primary company is not in some segments provided
            OperatorOptions::INCLUDING_ALL => $this->companySegmentRepository->isContactPrimaryCompanyInAllSegments($contactId, $segmentIds), // Contact's primary company is in all segments provided
            OperatorOptions::EXCLUDING_ALL => $this->companySegmentRepository->isNotContactPrimaryCompanyInAllSegments($contactId, $segmentIds), // Contact's primary company is not in all segments provided
            default                        => throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator)),
        };
    }
}
