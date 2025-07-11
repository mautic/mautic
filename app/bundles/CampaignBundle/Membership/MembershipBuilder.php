<?php

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadRepository as CampaignLeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Membership\Exception\RunLimitReachedException;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MembershipBuilder
{
    private ?Campaign $campaign = null;

    private ?ContactLimiter $contactLimiter = null;

    private ?int $runLimit = null;

    private ?OutputInterface $output = null;

    private ?\Symfony\Component\Console\Helper\ProgressBar $progressBar = null;

    public function __construct(
        private MembershipManager $manager,
        private CampaignLeadRepository $campaignLeadRepository,
        private LeadRepository $leadRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param int $runLimit
     */
    public function build(Campaign $campaign, ContactLimiter $contactLimiter, $runLimit, OutputInterface $output = null): int
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
     * Add contacts to a campaign.
     *
     * @throws RunLimitReachedException
     */
    private function addNewlyQualifiedMembers(int $totalContactsProcessed): int
    {
        $contactsProcessed = 0;

        if ($this->output) {
            $countResult = $this->campaignLeadRepository->getCountsForCampaignContactsBySegment(
                $this->campaign->getId(),
                $this->contactLimiter,
                $this->campaign->allowRestart()
            );

            $this->output->writeln(
                $this->translator->trans(
                    'mautic.campaign.rebuild.to_be_added',
                    ['%leads%' => $countResult->getCount(), '%batch%' => $this->contactLimiter->getBatchLimit()]
                )
            );

            if (0 === $countResult->getCount()) {
                // No use continuing
                return 0;
            }

            $this->startProgressBar($countResult->getCount());
        }

        $contacts = $this->campaignLeadRepository->getCampaignContactsBySegments(
            $this->campaign->getId(),
            $this->contactLimiter,
            $this->campaign->allowRestart()
        );

        while (count($contacts)) {
            // get an array of contact entities based on the contact id
            $contactCollection = $this->leadRepository->getContactCollection($contacts);
            if ($contactCollection->count() <= 0) {
                // Prevent endless loop just in case
                break;
            }

            // increase the total nr of contacts processed by this batch
            $contactsProcessed += $contactCollection->count();

            // Add the contacts to this segment
            $this->manager->addContacts($contactCollection, $this->campaign, false);

            // Clear Lead entities from RAM
            $this->leadRepository->detachEntities($contactCollection->toArray());

            // Have we hit the run limit?
            if ($this->runLimit && $contactsProcessed >= $this->runLimit) {
                $this->finishProgressBar();
                throw new RunLimitReachedException($contactsProcessed + $totalContactsProcessed);
            }

            // Get next batch
            $contacts = $this->campaignLeadRepository->getCampaignContactsBySegments(
                $this->campaign->getId(),
                $this->contactLimiter,
                $this->campaign->allowRestart()
            );
        }

        $this->finishProgressBar();

        return $contactsProcessed;
    }

    /**
     * @throws RunLimitReachedException
     */
    private function removeUnqualifiedMembers(int $totalContactsProcessed): int
    {
        $contactsProcessed = 0;

        if ($this->output) {
            $countResult = $this->campaignLeadRepository->getCountsForOrphanedContactsBySegments($this->campaign->getId(), $this->contactLimiter);

            $this->output->writeln(
                $this->translator->trans(
                    'mautic.lead.list.rebuild.to_be_removed',
                    ['%leads%' => $countResult->getCount(), '%batch%' => $this->contactLimiter->getBatchLimit()]
                )
            );

            if (0 === $countResult->getCount()) {
                // No use continuing
                return 0;
            }

            $this->startProgressBar($countResult->getCount());
        }

        $contacts = $this->campaignLeadRepository->getOrphanedContacts($this->campaign->getId(), $this->contactLimiter);
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
            $this->leadRepository->detachEntities($contactCollection->toArray());

            // Have we hit the run limit?
            if ($this->runLimit && $contactsProcessed >= $this->runLimit) {
                $this->finishProgressBar();
                throw new RunLimitReachedException($contactsProcessed + $totalContactsProcessed);
            }

            // Get next batch
            $contacts = $this->campaignLeadRepository->getOrphanedContacts($this->campaign->getId(), $this->contactLimiter);
        }

        $this->finishProgressBar();

        return $contactsProcessed;
    }

    private function startProgressBar(int $total): void
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

    private function finishProgressBar(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->output->writeln('');
        }
    }
}
