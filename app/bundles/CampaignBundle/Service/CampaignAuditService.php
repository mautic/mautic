<?php

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignAuditService
{
    public function __construct(
        private FlashBag $flashBag,
        private UrlGeneratorInterface $urlGenerator,
        private CampaignRepository $campaignRepository,
        private EmailRepository $emailRepository,
    ) {
    }

    public function addWarningForUnpublishedEmails(Campaign $campaign): void
    {
        $emailIds = $this->campaignRepository->fetchEmailIdsById($campaign->getId());
        $emails   = $this->emailRepository->findBy(['id' => $emailIds]);

        foreach ($emails as $email) {
            if (!$email->isPublished()) {
                $this->setEmailWarningFlashMessage($email);
            }
        }
    }

    private function setEmailWarningFlashMessage(Email $email): void
    {
        $this->flashBag->add(
            'mautic.core.notice.campaign.unpublished.email',
            [
                '%name%'      => $email->getName(),
                '%menu_link%' => 'mautic_email_index',
                '%url%'       => $this->urlGenerator->generate('mautic_email_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $email->getId(),
                ]),
            ],
            FlashBag::LEVEL_WARNING,
        );
    }
}
