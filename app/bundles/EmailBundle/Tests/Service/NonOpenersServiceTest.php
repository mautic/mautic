<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Service;

use Mautic\CoreBundle\Helper\CommandHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Service\NonOpenersService;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NonOpenersServiceTest extends TestCase
{
    private EmailModel&MockObject $emailModel;
    private ListModel&MockObject $listModel;
    private CommandHelper&MockObject $commandHelper;
    private NonOpenersService $service;

    protected function setUp(): void
    {
        $this->emailModel    = $this->createMock(EmailModel::class);
        $this->listModel     = $this->createMock(ListModel::class);
        $this->commandHelper = $this->createMock(CommandHelper::class);

        $this->service = new NonOpenersService(
            $this->emailModel,
            $this->listModel,
            $this->commandHelper,
        );
    }

    public function testCanResendReturnsTrueForValidSegmentEmail(): void
    {
        $email = $this->createEmailMock();
        $email->setEmailType('list');
        $email->method('getSendingStatus')->willReturn('sent');

        $this->assertTrue($this->service->canResend($email));
    }

    public function testCanResendReturnsFalseForTemplateEmail(): void
    {
        $email = $this->createEmailMock();
        $email->setEmailType('template');
        $email->method('getSendingStatus')->willReturn('sent');

        $this->assertFalse($this->service->canResend($email));
    }

    public function testCanResendReturnsFalseWhenStillSending(): void
    {
        $email = $this->createEmailMock();
        $email->setEmailType('list');
        $email->method('getSendingStatus')->willReturn('sending');

        $this->assertFalse($this->service->canResend($email));
    }

    public function testCanResendReturnsFalseWhenAlreadyResent(): void
    {
        $email = $this->createEmailMock();
        $email->setEmailType('list');
        $email->method('getSendingStatus')->willReturn('sent');

        // Simulate the inverse side of the relationship (Doctrine would handle this at runtime)
        $resend = new Email();
        $email->getResends()->add($resend);

        $this->assertFalse($this->service->canResend($email));
    }

    public function testCanResendReturnsFalseWhenIsAResendItself(): void
    {
        $original = new Email();

        $email = $this->createEmailMock();
        $email->setEmailType('list');
        $email->method('getSendingStatus')->willReturn('sent');
        $email->setResendOf($original);

        $this->assertFalse($this->service->canResend($email));
    }

    public function testResendThrowsForNonExistentEmail(): void
    {
        $this->emailModel->method('getEntity')->with(999)->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->resend(999);
    }

    public function testResendThrowsForNonResendableEmail(): void
    {
        $email = $this->createEmailMock();
        $email->setEmailType('template');
        $email->method('getSendingStatus')->willReturn('sent');

        $this->emailModel->method('getEntity')->with(1)->willReturn($email);

        $this->expectException(\LogicException::class);
        $this->service->resend(1);
    }

    /**
     * Creates an Email mock with the constructor enabled (to initialize collections)
     * while only mocking getSendingStatus() which depends on runtime state.
     */
    private function createEmailMock(): Email&MockObject
    {
        return $this->getMockBuilder(Email::class)
            ->onlyMethods(['getSendingStatus'])
            ->getMock();
    }
}
