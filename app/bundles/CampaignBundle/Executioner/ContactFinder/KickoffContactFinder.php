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
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFoundException;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class KickoffContactFinder
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * KickoffContactFinder constructor.
     *
     * @param LeadRepository     $leadRepository
     * @param CampaignRepository $campaignRepository
     * @param LoggerInterface    $logger
     */
    public function __construct(LeadRepository $leadRepository, CampaignRepository $campaignRepository, LoggerInterface $logger)
    {
        $this->leadRepository     = $leadRepository;
        $this->campaignRepository = $campaignRepository;
        $this->logger             = $logger;
    }

    /**
     * @param int            $campaignId
     * @param ContactLimiter $limiter
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
     * @param int            $campaignId
     * @param array          $eventIds
     * @param ContactLimiter $limiter
     *
     * @return int
     */
    public function getContactCount($campaignId, array $eventIds, ContactLimiter $limiter)
    {
        $countResult = $this->campaignRepository->getCountsForPendingContacts($campaignId, $eventIds, $limiter);

        return $countResult->getCount();
    }

    /**
     * Clear Lead entities from memory.
     */
    public function clear()
    {
        $this->leadRepository->clear();
    }
}
