<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Service;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CampaignAuditServiceFunctionalTest extends MauticMysqlTestCase
{
    private CampaignAuditService $campaignAuditService;
    private FlashBag&MockObject $flashBagMock;
    private UrlGeneratorInterface&MockObject $urlGeneratorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->flashBagMock        = $this->createMock(FlashBag::class);
        $this->urlGeneratorMock    = $this->createMock(UrlGeneratorInterface::class);

        $this->campaignAuditService = new CampaignAuditService(
            $this->flashBagMock,
            $this->urlGeneratorMock,
            static::getContainer()->get('mautic.campaign.repository.event')
        );
    }

    public function testWarningIsAddedForUnpublishedEmail(): void
    {
        // 1. Create a campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign with Unpublished Email');
        $this->em->persist($campaign);
        $this->em->flush();

        // 2. Create emails
        $publishedEmail = new Email();
        $publishedEmail->setName('Published Email for Campaign');
        $publishedEmail->setIsPublished(true);
        $publishedEmail->setEmailType('template');
        $this->em->persist($publishedEmail);

        $unpublishedEmail = new Email();
        $unpublishedEmail->setName('Unpublished Email for Campaign');
        $unpublishedEmail->setIsPublished(false);
        $unpublishedEmail->setEmailType('template');
        $this->em->persist($unpublishedEmail);
        $this->em->flush();

        // 3. Create campaign events linked to emails
        $publishedEmailEvent = new Event();
        $publishedEmailEvent->setCampaign($campaign);
        $publishedEmailEvent->setName('Published Email Event');
        $publishedEmailEvent->setEventType('email.send');
        $publishedEmailEvent->setType(Event::TYPE_ACTION);
        $publishedEmailEvent->setChannel('email');
        $publishedEmailEvent->setChannelId($publishedEmail->getId());
        $this->em->persist($publishedEmailEvent);

        $unpublishedEmailEvent = new Event();
        $unpublishedEmailEvent->setCampaign($campaign);
        $unpublishedEmailEvent->setName('Unpublished Email Event');
        $unpublishedEmailEvent->setEventType('email.send');
        $unpublishedEmailEvent->setType(Event::TYPE_ACTION);
        $unpublishedEmailEvent->setChannel('email');
        $unpublishedEmailEvent->setChannelId($unpublishedEmail->getId());
        $this->em->persist($unpublishedEmailEvent);
        $this->em->flush();

        // Expectation for UrlGeneratorInterface
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

        // Expectation for FlashBag
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

        // Call the service method
        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);
    }

    public function testNoWarningIsAddedWhenAllEmailsArePublished(): void
    {
        // 1. Create a campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign with All Published Emails');
        $this->em->persist($campaign);
        $this->em->flush();

        // 2. Create a published email
        $publishedEmail = new Email();
        $publishedEmail->setName('Another Published Email');
        $publishedEmail->setIsPublished(true);
        $publishedEmail->setEmailType('template');
        $this->em->persist($publishedEmail);
        $this->em->flush();

        // 3. Create a campaign event linked to the published email
        $emailEvent = new Event();
        $emailEvent->setCampaign($campaign);
        $emailEvent->setName('Published Email Event');
        $emailEvent->setEventType('email.send');
        $emailEvent->setType(Event::TYPE_ACTION);
        $emailEvent->setChannel('email');
        $emailEvent->setChannelId($publishedEmail->getId());
        $this->em->persist($emailEvent);
        $this->em->flush();

        // Expect no FlashBag call
        $this->flashBagMock->expects($this->never())
            ->method('add');

        // Call the service method
        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);
    }

    public function testNoWarningIsAddedForCampaignWithNoEmailEvents(): void
    {
        // 1. Create a campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign with No Email Events');
        $this->em->persist($campaign);
        $this->em->flush();

        // Expect no FlashBag call
        $this->flashBagMock->expects($this->never())
            ->method('add');

        // Call the service method
        $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);
    }
}
