<?php

namespace Mautic\EmailBundle\Tests\Model;

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

    /**
     * @var SendWinnerService
     */
    private $sendWinnerService;

    /**
     * @var VariantConverterService
     */
    private $variantConverterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModel              = $this->createMock(EmailModel::class);
        $this->abTestResultService     = $this->createMock(AbTestResultService::class);
        $this->abTestSettingsService   = new AbTestSettingsService();
        $this->variantConverterService = new VariantConverterService();
        $this->sendWinnerService       = new SendWinnerService(
            $this->emailModel,
            $this->abTestResultService,
            $this->abTestSettingsService
        );
    }

    public function testProcessWinnerEmailsWithNoWinners(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';
        $emailId         = 5;
        $variantId       = 7;

        $email = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();

        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));

        $email->setIsPublished(true);

        $variant = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId', 'getVariantParent'])
            ->getMock();
        $variant->setIsPublished(true);

        $variant->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($variantId));

        $email->addVariantChild($variant);
        $variant->setVariantParent($email);

        $variantSettings = ['totalWeight' => 40, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);
        $variantSettings = ['weight' => 25];
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

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => []]);

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

    public function testProcessWinnerEmails(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';
        $emailId         = 5;
        $variantId       = 7;

        $email = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));
        $email->setIsPublished(true);

        $variant = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
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

        $this->emailModel->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturnOnConsecutiveCalls($email, $variant);

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

        $converter = $this->variantConverterService;

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->will($this->returnCallback(
                function ($variant) use ($converter) {
                    $converter->convertWinnerVariant($variant);
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
                'Winner email 7 has been sent to remaining contacts.',
            ],
            $this->sendWinnerService->getOutputMessages()
        );
    }

    public function testProcessWinnerEmailsWithoutId(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;

        $email   = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($emailId));
        $email->setIsPublished(true);

        $variantId = 7;

        $variant   = $this->getMockBuilder(Email::class)
            ->onlyMethods(['getId'])
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
            ->willReturn(['criteria' => [$winnerCriteria => []]]);

        $this->abTestResultService->expects($this->once())
            ->method('getAbTestResult')
            ->with($email, [])
            ->willReturn(['winners' => [$variant]]);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($variant);

        $converter = $this->variantConverterService;

        $this->emailModel->expects($this->once())
            ->method('convertWinnerVariant')
            ->will($this->returnCallback(
                function ($variant) use ($converter) {
                    $converter->convertWinnerVariant($variant);
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

    public function testProcessWinnerEmailsNoDelay(): void
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

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsWrongTotalWeight(): void
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

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNoVariants(): void
    {
        $sendWinnerDelay = 2;
        $winnerCriteria  = 'email.openrate';

        $emailId = 5;
        $email   = new Email();
        $email->setIsPublished(true);

        $variantSettings = ['totalWeight' => 100, 'winnerCriteria' => $winnerCriteria, 'sendWinnerDelay' => $sendWinnerDelay];
        $email->setVariantSettings($variantSettings);

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->never())
            ->method('isReadyToSendWinner');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNoWinner(): void
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

        $this->emailModel->expects($this->once())
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

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }

    public function testProcessWinnerEmailsNotReady(): void
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

        $this->emailModel->expects($this->once())
            ->method('getEntity')
            ->with($emailId)
            ->willReturn($email);

        $this->emailModel->expects($this->once())
            ->method('isReadyToSendWinner')
            ->with(null, $sendWinnerDelay)
            ->willReturn(false);

        $this->abTestResultService->expects($this->never())
            ->method('getAbTestResult');

        $this->emailModel->expects($this->never())
            ->method('convertWinnerVariant');

        $this->sendWinnerService->processWinnerEmails($emailId);
    }
}
