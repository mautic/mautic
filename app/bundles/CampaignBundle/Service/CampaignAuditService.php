<?php

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignAuditService
{
    public function __construct(
        private FlashBag $flashBag,
        private UrlGeneratorInterface $urlGenerator,
        private EventRepository $eventRepository,
    ) {
    }

    public function addWarningForUnpublishedEmails(Campaign $campaign): void
    {
        $emails = $this->eventRepository->getCampaignEmailEvents($campaign->getId());

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
