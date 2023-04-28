<?php

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Model\AbTest\AbTestResultService;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\CoreBundle\Model\AbTest\VariantConverterService;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\AbTest\SendWinnerService;
use Mautic\EmailBundle\Model\EmailModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendWinnerServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EmailModel
     */
    private $emailModel;

    /**
     * @var MockObject|AbTestResultService
     */
    private $abTestResultService;

    /**
     * @var AbTestSettingsService
     */
    private $abTestSettingsService;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SendWinnerService
     */
    private $sendWinnerService;

    /**
     * @var VariantConverterService
     */
    private $variantConverterService;

    protected function setUp()
    {
        parent::setUp();

        $this->emailModel              = $this->createMock(EmailModel::class);
        $this->abTestResultService     = $this->createMock(AbTestResultService::class);
        $this->abTestSettingsService   = new AbTestSettingsService();
        $this->eventDispatcher         = $this->createMock(EventDispatcherInterface::class);
        $this->variantConverterService = new VariantConverterService();
        $this->sendWinnerService       = new SendWinnerService(
            $this->emailModel,
            $this->abTestResultService,
            $this->abTestSettingsService,
            $this->eventDispatcher
        );
    }

    public function testProcessWinnerEmailsWithNoWinners()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';
        $emailId         = 5;
        $variantId       = 7;

        /** @var Email @email */
        $email = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));
        $email->setIsPublished(true);

        /** @var Email @variant */
        $variant = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variant->setIsPublished(true);

        $variant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($variantId));

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
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

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => []]);

        $event = new ChannelBroadcastEvent('email', $variantId);
        $event->setAbTestWinner(true);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);

        Assert::assertSame(
            [
                "\n\nProcessing email id #5",
                'No winner yet.',
            ],
            $this->sendWinnerService->getOutputMessages()
        );
    }

    public function testProcessWinnerEmails()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';
        $emailId         = 5;
        $variantId       = 7;

        /** @var Email @email */
        $email = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));
        $email->setIsPublished(true);

        /** @var Email @variant */
        $variant = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variant->setIsPublished(true);

        $variant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($variantId));

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
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

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => [$variant->getId()]]);

        $this->emailModel->expects($this->at(3))
            ->method('getEntity')
            ->willReturn($variant);

        $event = new ChannelBroadcastEvent('email', $variantId);
        $event->setAbTestWinner(true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ChannelEvents::CHANNEL_BROADCAST, $event);

        $converter = $this->variantConverterService;

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->will($this->returnCallback(
                function ($variant) use ($converter) {
                    return $converter->convertWinnerVariant($variant);
                }
            ));

        $this->sendWinnerService->processWinnerEmails($emailId);

        $variantSettings = $variant->getVariantSettings();
        $this->assertEmpty($variant->getVariantParent());
        $this->assertTrue($variant->isPublished());
        $this->assertFalse($email->isPublished());
        $this->assertEquals($email->getVariantParent(), $variant);
        $this->assertEquals($variantSettings['totalWeight'], AbTestSettingsService::DEFAULT_TOTAL_WEIGHT);
        $this->assertEquals($variantSettings['winnerCriteria'], $winnerCriteria);

        Assert::assertSame(
            [
                "\n\nProcessing email id #5",
                'Winner ids: 7',
                'Winner email 7 will be send to remaining contacts.',
            ],
            $this->sendWinnerService->getOutputMessages()
        );
    }

    public function testProcessWinnerEmailsWithoutId()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;

        /** @var Email @email */
        $email   = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));
        $email->setIsPublished(true);

        $variantId = 7;

        /** @var Email @variant */
        $variant   = $this->getMockBuilder(Email::class)
            ->setMethods(['getId'])
            ->getMock();
        $variant->setIsPublished(true);

        $variant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($variantId));

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
            ->method('getEmailsToSendWinnerVariant')
            ->willReturn([$email]);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with($emailId, $sendWinnerDelay)
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('getBuilderComponents')
            ->with($email, 'abTestWinnerCriteria')
            ->willReturn(['criteria' => [$winnerCriteria => []]]);

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => [$variant]]);

        $this->emailModel->expects($this->at(3))
            ->method('getEntity')
            ->willReturn($variant);

        $event = new ChannelBroadcastEvent('email', $variantId);
        $event->setAbTestWinner(true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ChannelEvents::CHANNEL_BROADCAST, $event);

        $converter = $this->variantConverterService;

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->will($this->returnCallback(
                function ($variant) use ($converter) {
                    return $converter->convertWinnerVariant($variant);
                }
            )
            );

        $this->sendWinnerService->processWinnerEmails();

        $variantSettings = $variant->getVariantSettings();
        $this->assertEmpty($variant->getVariantParent());
        $this->assertTrue($variant->isPublished());
        $this->assertFalse($email->isPublished());
        $this->assertEquals($email->getVariantParent(), $variant);
        $this->assertEquals($variantSettings['totalWeight'], AbTestSettingsService::DEFAULT_TOTAL_WEIGHT);
        $this->assertEquals($variantSettings['winnerCriteria'], $winnerCriteria);
    }

    public function testProcessWinnerEmailsNoDelay()
    {
        $sendWinnerDelay = 0;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variant = new Email();
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
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

    public function testProcessWinnerEmailsWrongTotalWeight()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variant = new Email();
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 100, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
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

    public function testProcessWinnerEmailsNoVariants()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variantSettings = ['totalWeight' => 100, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
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

    public function testProcessWinnerEmailsNoWinner()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variant = new Email();
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with(null, $sendWinnerDelay)
            ->willReturn(true);

        $this->emailModel->expects($this->once())
            ->method('getBuilderComponents')
            ->with($email, 'abTestWinnerCriteria')
            ->willReturn(['criteria' => [$winnerCriteria => []]]);

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => []]);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNotReady()
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variant = new Email();
        $variant->setIsPublished(true);

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $variantSettings = ['weight' => 21];
        $variant->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->at(0))
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with(null, $sendWinnerDelay)
            ->willReturn(false);

        $this->abTestResultService->expects($this->never())
            ->method('getAbTestResult');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }
}
