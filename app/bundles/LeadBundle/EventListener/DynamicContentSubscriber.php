<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadListRepository
     */
    private $segmentRepository;

    public function __construct(LeadListRepository $segmentRepository)
    {
        $this->segmentRepository = $segmentRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['onContactFilterEvaluate', 0],
        ];
    }

    public function onContactFilterEvaluate(ContactFiltersEvaluateEvent $event): void
    {
        $contact = $event->getContact();
        $filters = $event->getFilters();

        foreach ($filters as $filter) {
            if ('leadlist' === $filter['type']) {
                // Segment membership evaluation. Check if contact/segment relationship is correct.
                $event->setIsMatched(
                    $this->isContactSegmentRelationshipValid($contact, $filter['operator'], $filter['filter'])
                );
                $event->setIsEvaluated(true);

                return;
            }
        }
    }

    /**
     * @param string $operator empty, !empty, in, !in
     *
     * @throws DBALException
     */
    private function isContactSegmentRelationshipValid(Lead $contact, string $operator, array $segmentIds = null): bool
    {
        $contactId = (int) $contact->getId(); // Use param with strict typehint

        switch ($operator) {
            case OperatorOptions::EMPTY:
                // Contact is not in any segment
                $return = $this->segmentRepository->isNotContactInAnySegment($contactId);
                break;
            case OperatorOptions::NOT_EMPTY:
                // Contact is in any segment
                $return = $this->segmentRepository->isContactInAnySegment($contactId);
                break;
            case OperatorOptions::IN:
                // Contact is in all segments provided in $segmentsIds
                $return = $this->segmentRepository->isContactInSegments($contactId, $segmentIds);
                break;
            case OperatorOptions::NOT_IN:
                // Contact is not in all segments provided in $segmentsIds
                $return = $this->segmentRepository->isNotContactInSegments($contactId, $segmentIds);
                break;
            default:
                throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator));
        }

        return $return;
    }
}
