<?php

namespace Mautic\CampaignBundle\Executioner\ContactFinder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class ScheduledContactFinder
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ScheduledContactFinder constructor.
     */
    public function __construct(LeadRepository $leadRepository, LoggerInterface $logger)
    {
        $this->leadRepository = $leadRepository;
        $this->logger         = $logger;
    }

    /**
     * Hydrate contacts with custom field value, companies, etc.
     */
    public function hydrateContacts(ArrayCollection $logs)
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
    }

    public function clear()
    {
        $this->leadRepository->clear();
    }
}
