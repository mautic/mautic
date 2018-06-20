<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignMemberRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\Exception\RunLimitReachedException;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MembershipBuilder
{
    /**
     * @var MembershipManager
     */
    private $manager;

    /**
     * @var CampaignMemberRepository
     */
    private $campaignMemberRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var ContactLimiter
     */
    private $contactLimiter;

    /**
     * @var int
     */
    private $runLimit;

    /**
     * @var OutputInterface|null
     */
    private $output;

    /**
     * MembershipBuilder constructor.
     *
     * @param MembershipManager        $manager
     * @param CampaignMemberRepository $campaignMemberRepository
     * @param LeadRepository           $leadRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        MembershipManager $manager,
        CampaignMemberRepository $campaignMemberRepository,
        LeadRepository $leadRepository,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator
    ) {
        $this->manager                  = $manager;
        $this->campaignMemberRepository = $campaignMemberRepository;
        $this->leadRepository           = $leadRepository;
        $this->eventDispatcher          = $eventDispatcher;
        $this->translator               = $translator;
    }

    /**
     * @param Campaign             $campaign
     * @param ContactLimiter       $contactLimiter
     * @param int                  $runLimit
     * @param OutputInterface|null $output
     *
     * @return int|void
     */
    public function build(Campaign $campaign, ContactLimiter $contactLimiter, $runLimit, OutputInterface $output = null)
    {
        defined('MAUTIC_REBUILDING_CAMPAIGNS') or define('MAUTIC_REBUILDING_CAMPAIGNS', 1);

        $this->campaign       = $campaign;
        $this->contactLimiter = $contactLimiter;
        $this->runLimit       = (int) $runLimit;
        $this->output         = $output;

        $contactsProcessed = 0;

        try {
            $contactsProcessed += $this->addNewlyQualifiedMembers();
        } catch (MaxContactsReachedException $exception) {
            // We're done for now
            return $exception->getContactsProcessed();
        }

        $contactsProcessed += $this->removeUnqualifiedMembers();

        return $contactsProcessed;
    }

    /**
     * @param $contactsProcessed
     *
     * @return int
     *
     * @throws RunLimitReachedException
     */
    private function addNewlyQualifiedMembers($contactsProcessed)
    {
        $progress = null;

        if ($this->output) {
            $countResult = $this->campaignMemberRepository->getCountsForCampaignContactsBySegment($this->campaign->getId(), $this->contactLimiter);

            $this->output->writeln(
                $this->translator->trans(
                    'mautic.campaign.rebuild.to_be_added',
                    ['%leads%' => $countResult->getCount(), '%batch%' => $this->contactLimiter->getBatchLimit()]
                )
            );

            if ($countResult->getCount() === 0) {
                // No use continuing
                return 0;
            }

            $progress = ProgressBarHelper::init($this->output, $countResult->getCount());
            $progress->start();

            // Notify the manager to increment progress as contacts are added
            $this->manager->setProgressBar($progress);
        }

        $contacts = $this->campaignMemberRepository->getCampaignContactsBySegments($this->campaign->getId(), $this->contactLimiter);
        while (count($contacts)) {
            $contactCollection = $this->leadRepository->getContactCollection($contacts);
            $contactsProcessed += $contactCollection->count();

            // Add the contacts to this segment
            $this->manager->addContacts($contactCollection, $this->campaign, false, true);

            // Clear Lead entities from RAM
            $this->leadRepository->clear();

            // Have we hit the run limit?
            if ($this->runLimit && $contactsProcessed >= $this->runLimit) {
                throw new RunLimitReachedException($contactsProcessed);
            }

            // Get next batch
            $contacts = $this->campaignMemberRepository->getCampaignContactsBySegments($this->campaign->getId(), $this->contactLimiter);
        }

        return $contactsProcessed;
    }

    private function removeUnqualifiedMembers()
    {
    }
}
