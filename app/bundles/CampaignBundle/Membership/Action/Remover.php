<?php

namespace Mautic\CampaignBundle\Membership\Action;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class Remover
{
    public const NAME = 'removed';

    private string $unscheduledMessage;

    public function __construct(
        private LeadRepository $leadRepository,
        private LeadEventLogRepository $leadEventLogRepository,
        TranslatorInterface $translator,
        DateHelper $dateHelper,
    ) {
        $dateRemoved              = $dateHelper->toFull(new \DateTime());
        $this->unscheduledMessage = $translator->trans('mautic.campaign.member.removed', ['%date%' => $dateRemoved]);
    }

    /**
     * @param bool $isExit
     *
     * @throws ContactAlreadyRemovedFromCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isExit): void
    {
        if ($isExit) {
            // Contact was removed by the change campaign action or a segment
            $campaignMember->setDateLastExited(new \DateTime());
        } else {
            $campaignMember->setDateLastExited(null);
        }

        if ($campaignMember->wasManuallyRemoved()) {
            $this->saveCampaignMember($campaignMember);

            // Contact was already removed from this campaign
            throw new ContactAlreadyRemovedFromCampaignException();
        }

        // Unschedule any scheduled events
        $this->leadEventLogRepository->unscheduleEvents($campaignMember, $this->unscheduledMessage);

        // Remove this contact from the campaign
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setManuallyAdded(false);

        $this->saveCampaignMember($campaignMember);
    }

    private function saveCampaignMember($campaignMember): void
    {
        $this->leadRepository->saveEntity($campaignMember);
        $this->leadRepository->detachEntity($campaignMember);
    }
}
