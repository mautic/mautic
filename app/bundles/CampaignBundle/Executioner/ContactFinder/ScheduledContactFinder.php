<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param LeadRepository  $leadRepository
     * @param LoggerInterface $logger
     */
    public function __construct(LeadRepository $leadRepository, LoggerInterface $logger)
    {
        $this->leadRepository = $leadRepository;
        $this->logger         = $logger;
    }

    /**
     * Hydrate contacts with custom field value, companies, etc.
     *
     * @param ArrayCollection $logs
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

        foreach ($logs as $log) {
            $contactId = $log->getLead()->getId();
            $contact   = $contacts->get($contactId);

            $log->setLead($contact);
        }
    }

    public function clear()
    {
        $this->leadRepository->clear();
    }
}
