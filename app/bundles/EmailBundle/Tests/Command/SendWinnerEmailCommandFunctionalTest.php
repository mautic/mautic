<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;

class SendWinnerEmailCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testInvalidEmailId(): void
    {
        $emailId         = 1;
        $commandResponse = $this->runCommand('mautic:email:sendwinner', ['--id' => $emailId], null, ExitCode::SUCCESS);
        Assert::assertStringContainsString(sprintf('Email id %s not found', $emailId), $commandResponse);
    }

    public function testEmailWithInvalidConfiguration(): void
    {
        $email           = $this->createEmail('Html');
        $commandResponse = $this->runCommand('mautic:email:sendwinner', ['--id' => $email->getId()], null, ExitCode::SUCCESS);
        Assert::assertStringContainsString('Amount of time to send winner email not specified in AB test variant settings.', $commandResponse);
    }

    private function createEmail(string $html): Email
    {
        $email = new Email();
        $email->setName('Test');
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }
}
