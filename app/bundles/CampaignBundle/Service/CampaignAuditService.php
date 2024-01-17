<?php

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignAuditService
{
    public const UNPUBLISHED_EMAIL_MESSAGE = 'mautic.core.notice.campaign.unpublished.email';
    public const EXPIRED_EMAIL_MESSAGE     = 'mautic.core.notice.campaign.expired.email';

    public function __construct(
        private FlashBag $flashBag,
        private UrlGeneratorInterface $urlGenerator,
        private CampaignRepository $campaignRepository,
        private EmailRepository $emailRepository,
        private DateTimeHelper $dateTimeHelper
    ) {
    }

    public function checkUnpublishedAndExpiredEmails(Campaign $campaign): void
    {
        $emailIds = $this->campaignRepository->fetchEmailIdsById($campaign->getId());
        $emails   = $this->emailRepository->findBy(['id' => $emailIds]);

        foreach ($emails as $email) {
            if (!$email->isPublished()) {
                $this->setEmailWarningFlashMessage($email, self::UNPUBLISHED_EMAIL_MESSAGE);
            }

            if ($this->isEmailExpired($email, $campaign)) {
                $this->setEmailWarningFlashMessage($email, self::EXPIRED_EMAIL_MESSAGE);
            }
        }
    }

    private function isEmailExpired(Email $email, Campaign $campaign): bool
    {
        $localDateTime = $this->dateTimeHelper->getLocalDateTime();
        if ($email->isPublished() && $email->getPublishDown()) {
            return $email->getPublishDown() < max($localDateTime, $campaign->getPublishDown());
        } else {
            return false;
        }
    }

    private function setEmailWarningFlashMessage(Email $email, string $message): void
    {
        $this->flashBag->add(
            $message,
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
