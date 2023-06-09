<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;

class SendWinnerEmailCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testInvalidEmailId(): void
    {
        $emailId         = 1;
        $commandTester   = $this->testSymfonyCommand('mautic:email:sendwinner', ['--id' => $emailId], null);
        Assert::assertStringContainsString(sprintf('Email id %s not found', $emailId), $commandTester->getDisplay());
    }

    public function testEmailWithInvalidConfiguration(): void
    {
        $email           = $this->createEmail('Html');
        $commandTester   = $this->testSymfonyCommand('mautic:email:sendwinner', ['--id' => $email->getId()], null);
        Assert::assertStringContainsString('Amount of time to send winner email not specified in AB test variant settings.', $commandTester->getDisplay());
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
