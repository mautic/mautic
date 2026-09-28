<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Mautic\CoreBundle\Model\AbTest\AbTestResultService;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\CoreBundle\Model\AbTest\VariantConverterService;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\AbTest\SendWinnerService;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SendWinnerServiceTest extends TestCase
{
    private EmailModel&MockObject $emailModel;

    private EventDispatcherInterface&MockObject $abTestDispatcher;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private SendWinnerService $sendWinnerService;

    private VariantConverterService $variantConverterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModel              = $this->createMock(EmailModel::class);
        $this->abTestDispatcher        = $this->createMock(EventDispatcherInterface::class);
        $abTestResultService     = new AbTestResultService($this->abTestDispatcher);
        $abTestSettingsService   = new AbTestSettingsService();
        $this->eventDispatcher         = $this->createMock(EventDispatcherInterface::class);
        $this->variantConverterService = new VariantConverterService();
        $this->sendWinnerService       = new SendWinnerService(
            $this->emailModel,
            $abTestResultService,
            $abTestSettingsService,
            $this->eventDispatcher
        );
    }

    public function testProcessWinnerEmails(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variantId = 7;
        $variant   = $this->createEmailMockWithId($variantId);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturnCallback(fn (mixed $id): Email => match (true) {
                $emailId === $id   => $email,
                $variantId === $id => $variant,
                default            => throw new \LogicException('Unexpected getEntity() argument'),
            });

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with($emailId, $sendWinnerDelay)
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('getBuilderComponents')
            ->with($email, 'abTestWinnerCriteria')
            ->willReturn(['criteria' => [$winnerCriteria => ['event' => 'mautic.email_determine_winner']]]);

        $this->abTestDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DetermineWinnerEvent::class), 'mautic.email_determine_winner')
            ->willReturnCallback(function (DetermineWinnerEvent $event) use ($variantId): DetermineWinnerEvent {
                $event->setAbTestResults(['winners' => [$variantId]]);

                return $event;
            });

        $event = new ChannelBroadcastEvent('email', $variantId);
        $event->setAbTestWinner(true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ChannelEvents::CHANNEL_BROADCAST)
            ->willReturnArgument(0);

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->willReturnCallback(function (Email $variant): void {
                $this->variantConverterService->convertWinnerVariant($variant);
            });

        $this->sendWinnerService->processWinnerEmails($emailId);

        $variantSettings = $variant->getVariantSettings();
        $this->assertNotInstanceOf(\Mautic\CoreBundle\Entity\VariantEntityInterface::class, $variant->getVariantParent());
        $this->assertTrue($variant->isPublished());
        $this->assertFalse($email->isPublished());
        $this->assertSame($variant, $email->getVariantParent());
        $this->assertEquals(AbTestSettingsService::DEFAULT_TOTAL_WEIGHT, $variantSettings['totalWeight']);
        $this->assertEquals($winnerCriteria, $variantSettings['winnerCriteria']);
    }

    public function testProcessWinnerEmailsWithoutId(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variantId = 7;
        $variant   = $this->createEmailMockWithId($variantId);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEmailsToSendWinnerVariant')
            ->willReturn([$email]);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with($emailId, $sendWinnerDelay)
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('getBuilderComponents')
            ->with($email, 'abTestWinnerCriteria')
            ->willReturn(['criteria' => [$winnerCriteria => ['event' => 'mautic.email_determine_winner']]]);

        $this->abTestDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(self::isInstanceOf(DetermineWinnerEvent::class), 'mautic.email_determine_winner')
            ->willReturnCallback(function (DetermineWinnerEvent $event) use ($variantId): DetermineWinnerEvent {
                $event->setAbTestResults(['winners' => [$variantId]]);

                return $event;
            });

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($variantId)
            ->willReturn($variant);

        $event = new ChannelBroadcastEvent('email', $variantId);
        $event->setAbTestWinner(true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ChannelEvents::CHANNEL_BROADCAST)
            ->willReturnArgument(0);

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->willReturnCallback(function (Email $variant): void {
                $this->variantConverterService->convertWinnerVariant($variant);
            });

        $this->sendWinnerService->processWinnerEmails();

        $variantSettings = $variant->getVariantSettings();
        $this->assertNotInstanceOf(\Mautic\CoreBundle\Entity\VariantEntityInterface::class, $variant->getVariantParent());
        $this->assertTrue($variant->isPublished());
        $this->assertFalse($email->isPublished());
        $this->assertSame($variant, $email->getVariantParent());
        $this->assertEquals(AbTestSettingsService::DEFAULT_TOTAL_WEIGHT, $variantSettings['totalWeight']);
        $this->assertEquals($winnerCriteria, $variantSettings['winnerCriteria']);
    }

    public function testProcessWinnerEmailsNoDelay(): void
    {
        $sendWinnerDelay = 0;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variant = $this->createEmailMockWithId(7);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsWrongTotalWeight(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variant = $this->createEmailMockWithId(7);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 100, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNoVariants(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variantSettings = ['totalWeight' => 100, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNoWinner(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variant = $this->createEmailMockWithId(7);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with($emailId, $sendWinnerDelay)
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('getBuilderComponents')
            ->with($email, 'abTestWinnerCriteria')
            ->willReturn(['criteria' => [$winnerCriteria => []]]);

        $this->abTestDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNotReady(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = $this->createEmailMockWithId($emailId);
        $email->setIsPublished(true);

        $variant = $this->createEmailMockWithId(7);
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with($emailId, $sendWinnerDelay)
            ->willReturn(false);

        $this->abTestDispatcher->expects($this->never())
            ->method('dispatch');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    /**
     * @return Email&MockObject
     */
    private function createEmailMockWithId(int $id): Email
    {
        $email = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $email->method('getId')->willReturn($id);

        return $email;
    }
}
