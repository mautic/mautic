<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailSubscriberTest extends TestCase
{
    /** @var MockObject&Config */
    private MockObject $config;
    /** @var MockObject&GrapesJsBuilderModel */
    private MockObject $grapesJsBuilderModel;
    /** @var MockObject&GrapesJsBuilderRepository */
    private MockObject $grapesJsBuilderRepo;
    private EmailModel|MockObject $emailModel;
    private EmailConfigInterface|MockObject $emailConfig;
    private EmailSubscriber $subscriber;

    public function setUp(): void
    {
        $this->config               = $this->createMock(Config::class);
        $this->grapesJsBuilderModel = $this->createMock(GrapesJsBuilderModel::class);
        $this->emailModel           = $this->createMock(EmailModel::class);
        $this->emailConfig          = $this->createMock(EmailConfigInterface::class);
        $this->grapesJsBuilderRepo  = $this->createMock(GrapesJsBuilderRepository::class);
        $this->subscriber           = new EmailSubscriber($this->config, $this->grapesJsBuilderModel, $this->emailModel, $this->emailConfig);

        $this->emailModel->method('getRepository')
            ->willReturn($this->createMock(EmailRepository::class));

        $this->grapesJsBuilderModel->method('getRepository')
            ->willReturn($this->grapesJsBuilderRepo);
    }

    public function testManageEmailDraftExitsWhenPluginNotPublished(): void
    {
        $event = $this->createMock(EmailEditSubmitEvent::class);

        $event->expects($this->never())
            ->method('getCurrentEmail');

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(false);

        $this->subscriber->manageEmailDraft($event);
    }

    public function testManageEmailDraftHandlesSaveAsDraft(): void
    {
        $event = $this->createMock(EmailEditSubmitEvent::class);

        $event->expects($this->once())
            ->method('getCurrentEmail')
            ->willReturn($this->createMock(Email::class));

        $event->expects($this->once())
            ->method('isSaveAsDraft')
            ->willReturn(true);

        $this->grapesJsBuilderRepo->method('findOneBy')
            ->willReturn($grapesJsBuilder = $this->createMock(GrapesJsBuilder::class));

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $grapesJsBuilder->expects($this->once())->method('setDraftCustomMjml');
        $grapesJsBuilder->expects($this->once())->method('setCustomMjml');

        $this->subscriber->manageEmailDraft($event);
    }

    public function testManageEmailDraftHandlesApply(): void
    {
        $event = $this->createMock(EmailEditSubmitEvent::class);

        $event->expects($this->once())
            ->method('getCurrentEmail')
            ->willReturn($this->createMock(Email::class));

        $event->expects($this->once())
            ->method('isApplyDraft')
            ->willReturn(true);

        $this->grapesJsBuilderRepo->method('findOneBy')
            ->willReturn($grapesJsBuilder = $this->createMock(GrapesJsBuilder::class));

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $grapesJsBuilder->expects($this->once())->method('setDraftCustomMjml');
        $grapesJsBuilder->expects($this->never())->method('setCustomMjml');

        $this->subscriber->manageEmailDraft($event);
    }

    public function testManageEmailDraftHandlesDiscardDraft(): void
    {
        $event = $this->createMock(EmailEditSubmitEvent::class);

        $event->expects($this->once())
            ->method('getCurrentEmail')
            ->willReturn($mockEmail = $this->createMock(Email::class));

        $event->expects($this->once())
            ->method('isDiscardDraft')
            ->willReturn(true);

        $mockEmail->expects($this->once())
            ->method('hasDraft')
            ->willReturn(true);

        $this->grapesJsBuilderRepo->method('findOneBy')
            ->willReturn($grapesJsBuilder = $this->createMock(GrapesJsBuilder::class));

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $grapesJsBuilder->expects($this->once())
            ->method('setDraftCustomMjml')
            ->with(null);

        $this->subscriber->manageEmailDraft($event);
    }
}
