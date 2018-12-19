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
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignLeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class InactiveContactFinder
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var CampaignLeadRepository
     */
    private $campaignLeadRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ArrayCollection
     */
    private $campaignMemberDatesAdded;

    /**
     * InactiveContactFinder constructor.
     *
     * @param LeadRepository         $leadRepository
     * @param CampaignRepository     $campaignRepository
     * @param CampaignLeadRepository $campaignLeadRepository
     * @param LoggerInterface        $logger
     */
    public function __construct(
        LeadRepository $leadRepository,
        CampaignRepository $campaignRepository,
        CampaignLeadRepository $campaignLeadRepository,
        LoggerInterface $logger
    ) {
        $this->leadRepository         = $leadRepository;
        $this->campaignRepository     = $campaignRepository;
        $this->campaignLeadRepository = $campaignLeadRepository;
        $this->logger                 = $logger;
    }

    /**
     * @param int            $campaignId
     * @param Event          $decisionEvent
     * @param ContactLimiter $limiter
     *
     * @return ArrayCollection
     *
     * @throws NoContactsFoundException
     */
    public function getContacts($campaignId, Event $decisionEvent, ContactLimiter $limiter)
    {
        if ($limiter->hasCampaignLimit() && 0 === $limiter->getCampaignLimitRemaining()) {
            // Limit was reached but do not trigger the NoContactsFoundException
            return new ArrayCollection();
        }

        // Get list of all campaign leads
        $decisionParentEvent            = $decisionEvent->getParent();
        $this->campaignMemberDatesAdded = $this->campaignLeadRepository->getInactiveContacts(
            $campaignId,
            $decisionEvent->getId(),
            ($decisionParentEvent) ? $decisionParentEvent->getId() : null,
            $limiter
        );

        if (empty($this->campaignMemberDatesAdded)) {
            // No new contacts found in the campaign
            throw new NoContactsFoundException();
        }

        $campaignContacts = array_keys($this->campaignMemberDatesAdded);
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
     * @return ArrayCollection
     */
    public function getDatesAdded()
    {
        return $this->campaignMemberDatesAdded;
    }

    /**
     * @param int            $campaignId
     * @param array          $decisionEvents
     * @param ContactLimiter $limiter
     *
     * @return int
     */
    public function getContactCount($campaignId, array $decisionEvents, ContactLimiter $limiter)
    {
        return $this->campaignLeadRepository->getInactiveContactCount($campaignId, $decisionEvents, $limiter);
    }

    /**
     * Clear Lead entities from memory.
     */
    public function clear()
    {
        $this->leadRepository->clear();
    }
}
