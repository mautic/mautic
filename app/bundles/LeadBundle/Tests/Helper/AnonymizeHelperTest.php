<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\AnonymizeHelper;

class AnonymizeHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testEmailWithDomain(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newDomain = 'ano.nym';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, false, 0, $newDomain);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString($newDomain, $newEmail);
    }

    public function testEmailWithDomainWithLimit(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newDomain = 'ano.nym';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, false, 64, $newDomain);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString($newDomain, $newEmail);
        $this->assertCount(64, str_split($newEmail));
    }

    public function testEmailWithNoDomainWithLimit(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, false, 64);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertCount(64, str_split($newEmail));
    }

    public function testEmailWithoutDomain(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString(AnonymizeHelper::PRE_DEFINED_DOMAIN, $newEmail);
    }

    public function testEmailInvalid(): void
    {
        $pureEmail = 'teste';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail);
        $this->assertEmpty($newEmail);
    }

    public function testText(): void
    {
        $text    = 'Teste';
        $newText = AnonymizeHelper::anonymizeText($text);
        $this->assertNotSame($text, $newText);
    }
}
