<?php

namespace Mautic\CampaignBundle\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class ScheduledContactFinder
{
    public function __construct(
        private LeadRepository $leadRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Hydrate contacts with custom field value, companies, etc.
     *
     * @return Collection<int, Lead>
     */
    public function hydrateContacts(ArrayCollection $logs): Collection
    {
        $contactIds = [];
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $contactIds[] = $log->getLead()->getId();
        }

        if (!count($contactIds)) {
            // Just a precaution in case non-existent contacts are lingering in the campaign leads table
            $this->logger->debug('CAMPAIGN: No contact entities found.');

            throw new NoContactsFoundException();
        }

        $contacts = $this->leadRepository->getContactCollection($contactIds);

        foreach ($logs as $key => $log) {
            $contactId = $log->getLead()->getId();
            if (!$contact = $contacts->get($contactId)) {
                // the contact must have been deleted mid execution so remove this log from memory
                $logs->remove($key);

                continue;
            }

            $log->setLead($contact);
        }

        return $contacts;
    }

    /**
     * Clear Lead entities from memory.
     *
     * @param Collection<int, Lead> $contacts
     */
    public function clear(Collection $contacts): void
    {
        $this->leadRepository->detachEntities($contacts->toArray());
    }
}
