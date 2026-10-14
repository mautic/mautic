<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailDraft;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Event\EmailEditSubmitEvent;
use Mautic\EmailBundle\Helper\EmailConfigInterface;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilder;
use MauticPlugin\GrapesJsBuilderBundle\Entity\GrapesJsBuilderRepository;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
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
            ->willReturn($grapesJsBuilder = new GrapesJsBuilder());

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->subscriber->manageEmailDraft($event);

        $this->assertSame('', $grapesJsBuilder->getCustomMjml());
        $this->assertNull($grapesJsBuilder->getDraftCustomMjml());
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
            ->willReturn($grapesJsBuilder = new GrapesJsBuilder());

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->subscriber->manageEmailDraft($event);

        $this->assertNull($grapesJsBuilder->getDraftCustomMjml());
        $this->assertNull($grapesJsBuilder->getCustomMjml());
    }

    public function testManageEmailDraftHandlesDiscardDraft(): void
    {
        $event = $this->createMock(EmailEditSubmitEvent::class);

        $email = new Email();
        $email->setDraft(new EmailDraft($email, null, null));

        $event->expects($this->once())
            ->method('getCurrentEmail')
            ->willReturn($email);

        $event->expects($this->once())
            ->method('isDiscardDraft')
            ->willReturn(true);

        $grapesJsBuilder = new GrapesJsBuilder();
        $grapesJsBuilder->setDraftCustomMjml('<mjml>draft</mjml>');

        $this->grapesJsBuilderRepo->expects($this->once())->method('findOneBy')
            ->willReturn($grapesJsBuilder);

        $this->config->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $this->subscriber->manageEmailDraft($event);

        $this->assertNull($grapesJsBuilder->getDraftCustomMjml());
    }
}
