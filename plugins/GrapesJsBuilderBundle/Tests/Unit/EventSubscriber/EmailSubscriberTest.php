<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class EmailSubscriberTest extends TestCase
{
    /**
     * @var MockObject&Config
     */
    private MockObject $config;

    /**
     * @var MockObject&GrapesJsBuilderRepository
     */
    private MockObject $grapesJsBuilderRepo;

    private EmailSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->config              = $this->createMock(Config::class);
        $this->grapesJsBuilderRepo = $this->createMock(GrapesJsBuilderRepository::class);
        $this->subscriber          = new EmailSubscriber(
            $this->config,
            $this->createStub(GrapesJsBuilderModel::class),
            $this->createStub(EmailConfigInterface::class),
            $this->grapesJsBuilderRepo,
            $this->createStub(EmailRepository::class)
        );
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
            ->willReturn($this->createStub(Email::class));

        $event->expects($this->once())
            ->method('isSaveAsDraft')
            ->willReturn(true);

        $this->grapesJsBuilderRepo->expects($this->once())->method('findOneBy')
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
            ->willReturn($this->createStub(Email::class));

        $event->expects($this->once())
            ->method('isApplyDraft')
            ->willReturn(true);

        $this->grapesJsBuilderRepo->expects($this->once())->method('findOneBy')
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

        $this->grapesJsBuilderRepo->expects($this->once())->method('findOneBy')
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
