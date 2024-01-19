<?php

namespace Mautic\CampaignBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignAuditServiceTest extends TestCase
{
    private MockObject $flashBag;
    private MockObject $urlGenerator;
    private MockObject $campaignRepository;
    private MockObject $emailRepository;
    private CampaignAuditService $campaignAuditService;

    protected function setUp(): void
    {
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->urlGenerator       = $this->createMock(UrlGeneratorInterface::class);
        $this->campaignRepository = $this->createMock(CampaignRepository::class);
        $this->emailRepository    = $this->createMock(EmailRepository::class);

        $this->campaignAuditService = new CampaignAuditService(
            $this->flashBag,
            $this->urlGenerator,
            $this->campaignRepository,
            $this->emailRepository,
        );
    }

    public function testAddWarningForUnpublishedEmails(): void
    {
        $campaign = new Campaign();
        $campaign->setPublishDown(new \DateTime('-1 day'));

        $email1 = new Email();
        $email1->setIsPublished(false);

        $email2 = new Email();
        $email2->setIsPublished(true);
        $email2->setPublishDown(new \DateTime('-1 day'));

        $this->campaignRepository->expects($this->once())
            ->method('fetchEmailIdsById')
            ->with($campaign->getId())
            ->willReturn([1, 2]);

        $this->emailRepository->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn([$email1, $email2]);

        $this->urlGenerator->expects($this->exactly(2))
            ->method('generate')
           ->willReturnOnConsecutiveCalls(
               '/s/emails/edit/1',
               '/s/emails/edit/2'
           );

        $this->flashBag->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'mautic.core.notice.campaign.unpublished.email',
                    [
                        '%name%'      => null,
                        '%menu_link%' => 'mautic_email_index',
                        '%url%'       => '/s/emails/edit/1',
                    ],
                    FlashBag::LEVEL_WARNING,
                ],
                [
                    'mautic.core.notice.campaign.unpublished.email',
                    [
                        '%name%'      => null,
                        '%menu_link%' => 'mautic_email_index',
                        '%url%'       => '/s/emails/edit/2',
                    ],
                    FlashBag::LEVEL_WARNING,
                ]
            );

        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);
    }
}
