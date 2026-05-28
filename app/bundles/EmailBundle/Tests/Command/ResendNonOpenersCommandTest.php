<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Command;

use Mautic\EmailBundle\Command\ResendNonOpenersCommand;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Service\NonOpenersService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ResendNonOpenersCommandTest extends TestCase
{
    private NonOpenersService&MockObject $service;
    private EmailModel&MockObject $emailModel;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->service    = $this->createMock(NonOpenersService::class);
        $this->emailModel = $this->createMock(EmailModel::class);

        $command     = new ResendNonOpenersCommand($this->service, $this->emailModel);
        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($application->find('mautic:emails:resend-nonopeners'));
    }

    public function testDryRunShowsEligibility(): void
    {
        $email = $this->createMock(Email::class);
        $email->method('getName')->willReturn('Test Email');
        $this->emailModel->method('getEntity')->with(42)->willReturn($email);
        $this->service->method('canResend')->with($email)->willReturn(true);

        $this->commandTester->execute([
            'email-id'  => '42',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Dry run', $this->commandTester->getDisplay());
    }

    public function testExecuteCallsServiceAndShowsResult(): void
    {
        $email = $this->createMock(Email::class);
        $email->method('getName')->willReturn('Test Email');
        $this->emailModel->method('getEntity')->with(42)->willReturn($email);
        $this->service->method('canResend')->with($email)->willReturn(true);
        $this->service->method('resend')->with(42)->willReturn(['emailId' => 100, 'segmentIds' => [200]]);

        $this->commandTester->execute(['email-id' => '42']);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('100', $this->commandTester->getDisplay());
    }

    public function testFailsForNonExistentEmail(): void
    {
        $this->emailModel->method('getEntity')->with(999)->willReturn(null);

        $this->commandTester->execute(['email-id' => '999']);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('not found', $this->commandTester->getDisplay());
    }

    public function testFailsForNonResendableEmail(): void
    {
        $email = $this->createMock(Email::class);
        $email->method('getName')->willReturn('Test Email');
        $this->emailModel->method('getEntity')->with(42)->willReturn($email);
        $this->service->method('canResend')->with($email)->willReturn(false);

        $this->commandTester->execute(['email-id' => '42']);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('cannot be resent', $this->commandTester->getDisplay());
    }
}
