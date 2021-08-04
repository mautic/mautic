<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        $commandResponse = $this->runCommand('mautic:email:sendwinner', ['--id' => $emailId], null, ExitCode::TEMPORARY_FAILURE);
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
