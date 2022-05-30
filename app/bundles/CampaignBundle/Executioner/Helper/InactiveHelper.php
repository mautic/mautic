<?php

namespace Mautic\CampaignBundle\Executioner\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Psr\Log\LoggerInterface;

class InactiveHelper
{
    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var InactiveContactFinder
     */
    private $inactiveContactFinder;

    /**
     * @var LeadEventLogRepository
     */
    private $eventLogRepository;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTime
     */
    private $earliestInactiveDate;

    /**
     * InactiveHelper constructor.
     */
    public function __construct(
        EventScheduler $scheduler,
        InactiveContactFinder $inactiveContactFinder,
        LeadEventLogRepository $eventLogRepository,
        EventRepository $eventRepository,
        LeadRepository $leadRepository,
        LoggerInterface $logger
    ) {
        $this->scheduler               = $scheduler;
        $this->inactiveContactFinder   = $inactiveContactFinder;
        $this->eventLogRepository      = $eventLogRepository;
        $this->eventRepository         = $eventRepository;
        $this->leadRepository          = $leadRepository;
        $this->logger                  = $logger;
    }

    /**
     * @param ArrayCollection|Event[] $decisions
     */
    public function removeDecisionsWithoutNegativeChildren(ArrayCollection $decisions)
    {
        /**
         * @var int
         * @var Event $decision
         */
        foreach ($decisions as $key => $decision) {
            $negativeChildren = $decision->getNegativeChildren();
            if (!$negativeChildren->count()) {
                $decisions->remove($key);
            }
        }
    }

    /**
     * @param int $lastActiveEventId
     *
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function removeContactsThatAreNotApplicable(
        \DateTime $now,
        ArrayCollection $contacts,
        $lastActiveEventId,
        ArrayCollection $negativeChildren,
        Event $event
    ) {
        $contactIds                 = $contacts->getKeys();
        $lastActiveDates            = $this->getLastActiveDates($lastActiveEventId, $contactIds);
        $this->earliestInactiveDate = $now;

        $parentEvent = $event->getParent();

        foreach ($contactIds as $contactId) {
            if (null !== $parentEvent && null !== $event->getDecisionPath()) {
                $rotation    = $this->leadRepository->getContactRotations([$contactId], $event->getCampaign()->getId());
                $log         = $parentEvent->getLogByContactAndRotation($contacts->get($contactId), $rotation);

                if (null === $log) {
                    $contacts->remove($contactId);
                }

                $pathTaken   = (int) $log->getNonActionPathTaken();

                if (1 === $pathTaken && !$parentEvent->getNegativeChildren()->contains($event)) {
                    $contacts->remove($contactId);
                    continue;
                } elseif (0 === $pathTaken && !$parentEvent->getPositiveChildren()->contains($event)) {
                    $contacts->remove($contactId);
                    continue;
                }
            }

            if (!isset($lastActiveDates[$contactId])) {
                // This contact does not have a last active date so likely the event is scheduled
                $contacts->remove($contactId);

                $this->logger->debug('CAMPAIGN: Contact ID# '.$contactId.' does not have a last active date ('.$lastActiveEventId.')');

                continue;
            }

            $earliestContactInactiveDate = $this->getEarliestInactiveDate($negativeChildren, $lastActiveDates[$contactId]);
            $this->logger->debug(
                'CAMPAIGN: Earliest date for inactivity for contact ID# '.$contactId.' is '.
                $earliestContactInactiveDate->format('Y-m-d H:i:s T').' based on last active date of '.
                $lastActiveDates[$contactId]->format('Y-m-d H:i:s T')
            );

            if ($this->earliestInactiveDate < $earliestContactInactiveDate) {
                $this->earliestInactiveDate = $earliestContactInactiveDate;
            }

            // If any are found to be inactive, we process or schedule all the events associated with the inactive path of a decision
            if ($earliestContactInactiveDate > $now) {
                $contacts->remove($contactId);
                $this->logger->debug('CAMPAIGN: Contact ID# '.$contactId.' has been active and thus not applicable');

                continue;
            }

            $this->logger->debug('CAMPAIGN: Contact ID# '.$contactId.' has not been active');
        }
    }

    /**
     * @return \DateTime
     */
    public function getEarliestInactiveDateTime()
    {
        return $this->earliestInactiveDate;
    }

    /**
     * @param $decisionId
     *
     * @return ArrayCollection
     */
    public function getCollectionByDecisionId($decisionId)
    {
        $collection = new ArrayCollection();

        /** @var Event $decision */
        if ($decision = $this->eventRepository->find($decisionId)) {
            $collection->set($decision->getId(), $decision);
        }

        return $collection;
    }

    /**
     * @return \DateTime|null
     *
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function getEarliestInactiveDate(ArrayCollection $negativeChildren, \DateTime $lastActiveDate)
    {
        $earliestDate = null;
        foreach ($negativeChildren as $event) {
            $executionDate = $this->scheduler->getExecutionDateTime($event, $lastActiveDate);
            if (!$earliestDate || $executionDate < $earliestDate) {
                $earliestDate = $executionDate;
            }
        }

        return $earliestDate;
    }

    /**
     * @param $lastActiveEventId
     *
     * @return array|ArrayCollection
     */
    private function getLastActiveDates($lastActiveEventId, array $contactIds)
    {
        // If there is a parent ID, get last active dates based on when that event was executed for the given contact
        // Otherwise, use when the contact was added to the campaign for comparison
        if ($lastActiveEventId) {
            return $this->eventLogRepository->getDatesExecuted($lastActiveEventId, $contactIds);
        }

        return $this->inactiveContactFinder->getDatesAdded();
    }
}
