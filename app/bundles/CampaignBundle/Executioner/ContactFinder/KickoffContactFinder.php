<?php

namespace Mautic\CampaignBundle\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class KickoffContactFinder
{
    public function __construct(
        private LeadRepository $leadRepository,
        private CampaignRepository $campaignRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param int $campaignId
     *
     * @return ArrayCollection
     *
     * @throws NoContactsFoundException
     */
    public function getContacts($campaignId, ContactLimiter $limiter)
    {
        // Get list of all campaign leads; start is always zero in practice because of $pendingOnly
        $campaignContacts = $this->campaignRepository->getPendingContactIds($campaignId, $limiter);

        if (empty($campaignContacts)) {
            // No new contacts found in the campaign

            throw new NoContactsFoundException();
        }

        $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', $campaignContacts));

        // Fetch entity objects for the found contacts
        $contacts = $this->leadRepository->getContactCollection($campaignContacts);

        if (!count($contacts)) {
            // Just a precaution in case non-existent contacts are lingering in the campaign leads table
            $this->logger->debug('CAMPAIGN: No contact entities found.');

            throw new NoContactsFoundException();
        }

        return $contacts;
    }

    /**
     * @param int $campaignId
     */
    public function getContactCount($campaignId, array $eventIds, ContactLimiter $limiter): int
    {
        $countResult = $this->campaignRepository->getCountsForPendingContacts($campaignId, $eventIds, $limiter);

        return $countResult->getCount();
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
