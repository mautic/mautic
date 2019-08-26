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
use Symfony\Component\Console\Helper\ProgressBar;
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
     * @var ProgressBar|null
     */
    private $progressBar;

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
     * @return int
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
            $contactsProcessed += $this->addNewlyQualifiedMembers($contactsProcessed);
        } catch (RunLimitReachedException $exception) {
            return $exception->getContactsProcessed();
        }

        try {
            $contactsProcessed += $this->removeUnqualifiedMembers($contactsProcessed);
        } catch (RunLimitReachedException $exception) {
            return $exception->getContactsProcessed();
        }

        return $contactsProcessed;
    }

    /**
     * @param $totalContactsProcessed
     *
     * @return int
     *
     * @throws RunLimitReachedException
     */
    private function addNewlyQualifiedMembers($totalContactsProcessed)
    {
        $progress          = null;
        $contactsProcessed = 0;

        if ($this->output) {
            $countResult = $this->campaignMemberRepository->getCountsForCampaignContactsBySegment($this->campaign->getId(), $this->contactLimiter, $this->campaign->allowRestart());

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

            $this->startProgressBar($countResult->getCount());
        }

        $contacts = $this->campaignMemberRepository->getCampaignContactsBySegments($this->campaign->getId(), $this->contactLimiter, $this->campaign->allowRestart());

        while (count($contacts)) {
            $contactCollection = $this->leadRepository->getContactCollection($contacts);
            if (!$contactCollection->count()) {
                // Prevent endless loop just in case
                break;
            }

            $contactsProcessed += $contactCollection->count();

            // Add the contacts to this segment
            $this->manager->addContacts($contactCollection, $this->campaign, false);

            // Clear Lead entities from RAM
            $this->leadRepository->clear();

            // Have we hit the run limit?
            if ($this->runLimit && $contactsProcessed >= $this->runLimit) {
                $this->finishProgressBar();
                throw new RunLimitReachedException($contactsProcessed + $totalContactsProcessed);
            }

            // Get next batch
            $contacts = $this->campaignMemberRepository->getCampaignContactsBySegments($this->campaign->getId(), $this->contactLimiter);
        }

        $this->finishProgressBar();

        return $contactsProcessed;
    }

    /**
     * @param $totalContactsProcessed
     *
     * @return int
     *
     * @throws RunLimitReachedException
     */
    private function removeUnqualifiedMembers($totalContactsProcessed)
    {
        $progress          = null;
        $contactsProcessed = 0;

        if ($this->output) {
            $countResult = $this->campaignMemberRepository->getCountsForOrphanedContactsBySegments($this->campaign->getId(), $this->contactLimiter);

            $this->output->writeln(
                $this->translator->trans(
                    'mautic.lead.list.rebuild.to_be_removed',
                    ['%leads%' => $countResult->getCount(), '%batch%' => $this->contactLimiter->getBatchLimit()]
                )
            );

            if ($countResult->getCount() === 0) {
                // No use continuing
                return 0;
            }

            $this->startProgressBar($countResult->getCount());
        }

        $contacts = $this->campaignMemberRepository->getOrphanedContacts($this->campaign->getId(), $this->contactLimiter);
        while (count($contacts)) {
            $contactCollection = $this->leadRepository->getContactCollection($contacts);
            if (!$contactCollection->count()) {
                // Prevent endless loop just in case
                break;
            }

            $contactsProcessed += $contactCollection->count();

            // Add the contacts to this segment
            $this->manager->removeContacts($contactCollection, $this->campaign, true);

            // Clear Lead entities from RAM
            $this->leadRepository->clear();

            // Have we hit the run limit?
            if ($this->runLimit && $contactsProcessed >= $this->runLimit) {
                $this->finishProgressBar();
                throw new RunLimitReachedException($contactsProcessed + $totalContactsProcessed);
            }

            // Get next batch
            $contacts = $this->campaignMemberRepository->getOrphanedContacts($this->campaign->getId(), $this->contactLimiter);
        }

        $this->finishProgressBar();

        return $contactsProcessed;
    }

    /**
     * @param $total
     */
    private function startProgressBar($total)
    {
        if (!$this->output) {
            $this->progressBar = null;
            $this->manager->setProgressBar($this->progressBar);

            return;
        }

        $this->progressBar = ProgressBarHelper::init($this->output, $total);
        $this->progressBar->start();

        // Notify the manager to increment progress as contacts are added
        $this->manager->setProgressBar($this->progressBar);
    }

    private function finishProgressBar()
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->output->writeln('');
        }
    }
}
