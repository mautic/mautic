<?php

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\LeadBundle\Helper\AnonymizeHelper;

class AnonymizeHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testEmailWithDomain(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newDomain = 'ano.nym';
        $newEmail  = AnonymizeHelper::email($pureEmail, $newDomain);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString($newDomain, $newEmail);
    }

    public function testEmailWithoutDomain(): void
    {
        $pureEmail = 'teste@gmail.com';
        $newEmail  = AnonymizeHelper::email($pureEmail);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString(AnonymizeHelper::PRE_DEFINED_DOMAIN, $newEmail);
    }

    public function testEmailInvalid(): void
    {
        $pureEmail = 'teste';
        $newEmail  = AnonymizeHelper::email($pureEmail);
        $this->assertFalse($newEmail);
    }

    public function testText(): void
    {
        $text    = 'Teste';
        $newText = AnonymizeHelper::text($text);
        $this->assertNotSame($text, $newText);
    }
}
