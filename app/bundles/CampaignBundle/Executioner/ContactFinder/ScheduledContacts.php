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
use Mautic\LeadBundle\Entity\LeadRepository;

class ScheduledContacts
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * ScheduledContacts constructor.
     *
     * @param LeadRepository $leadRepository
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * Hydrate contacts with custom field value, companies, etc.
     *
     * @param ArrayCollection $logs
     *
     * @return ArrayCollection
     */
    public function hydrateContacts(ArrayCollection $logs)
    {
        $contactIds = [];
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $contactIds[] = $log->getLead()->getId();
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
