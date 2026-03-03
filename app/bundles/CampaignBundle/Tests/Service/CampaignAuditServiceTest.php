<?php

namespace Mautic\CampaignBundle\Tests\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampaignAuditServiceTest extends MauticMysqlTestCase
{
    private const CAMPAIGN_NAME = 'Test Campaign';
    private CampaignAuditService $campaignAuditService;
    private FlashBag|MockObject $flashBagMock;
    private UrlGeneratorInterface|MockObject $urlGeneratorMock;
    private EventRepository|MockObject $eventRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flashBagMock        = $this->createMock(FlashBag::class);
        $this->urlGeneratorMock    = $this->createMock(UrlGeneratorInterface::class);
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);

        $this->campaignAuditService = new CampaignAuditService(
            $this->flashBagMock,
            $this->urlGeneratorMock,
            $this->eventRepositoryMock
        );
    }

    public function testWarningIsAddedForUnpublishedEmail(): void
    {
        $campaign = new Campaign();
        $campaign->setName(self::CAMPAIGN_NAME);
        $this->em->persist($campaign);

        $publishedEmail = new Email();
        $publishedEmail->setName('Published Email');
        $publishedEmail->setIsPublished(true);
        $this->em->persist($publishedEmail);

        $unpublishedEmail = new Email();
        $unpublishedEmail->setName('Unpublished Email');
        $unpublishedEmail->setIsPublished(false);
        $this->em->persist($unpublishedEmail);

        $this->em->flush(); // Ensure entities are flushed to get IDs

        $this->eventRepositoryMock->expects($this->once())
            ->method('getCampaignEmailEvents')
            ->with($campaign->getId())
            ->willReturn([$publishedEmail, $unpublishedEmail]);

        // Expectation for UrlGeneratorInterface (moved before service call)
        $this->urlGeneratorMock->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_email_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => $unpublishedEmail->getId(),
                ]
            )
            ->willReturn('/s/emails/edit/'.$unpublishedEmail->getId());

        // Expectation for FlashBag (moved before service call, with detailed arguments restored)
        $this->flashBagMock->expects($this->once())
            ->method('add')
            ->with(
                'mautic.core.notice.campaign.unpublished.email',
                $this->callback(function (array $messageVars) use ($unpublishedEmail) {
                    $this->assertStringContainsString($unpublishedEmail->getName(), $messageVars['%name%']);
                    $this->assertStringContainsString('mautic_email_index', $messageVars['%menu_link%']);

                    return true;
                }),
                FlashBag::LEVEL_WARNING
            );

        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign); // This is the call under test
    }

    public function testNoWarningIsAddedWhenAllEmailsArePublished(): void
    {
        $campaign = new Campaign();
        $campaign->setName(self::CAMPAIGN_NAME);
        $this->em->persist($campaign);

        $publishedEmail = new Email();
        $publishedEmail->setName('Published Email');
        $publishedEmail->setIsPublished(true);
        $this->em->persist($publishedEmail);

        $this->em->flush();

        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);

        $this->flashBagMock->expects($this->never())
            ->method('add');
    }

    public function testNoWarningIsAddedForCampaignWithNoEmails(): void
    {
        $campaign = new Campaign();
        $campaign->setName(self::CAMPAIGN_NAME);
        $this->em->persist($campaign);
        $this->em->flush();

        $this->eventRepositoryMock->expects($this->once())
            ->method('getCampaignEmailEvents')
            ->with($campaign->getId())
            ->willReturn([]);

        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);

        $this->flashBagMock->expects($this->never())
            ->method('add');
    }
}
