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

    public function testEmailPseudonymized(): void
    {
        $pureEmail = 'test@example.com';
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, true);
        $this->assertNotSame($pureEmail, $newEmail);
        $this->assertStringContainsString(AnonymizeHelper::PRE_PSEUDONYMIZED_DOMAIN, $newEmail);
    }

    public function testEmailPseudonymizedIgnoresCustomDomain(): void
    {
        $pureEmail    = 'test@example.com';
        $customDomain = 'custom.domain';
        $newEmail     = AnonymizeHelper::anonymizeEmail($pureEmail, true, 0, $customDomain);
        $this->assertStringContainsString(AnonymizeHelper::PRE_PSEUDONYMIZED_DOMAIN, $newEmail);
        $this->assertStringNotContainsString($customDomain, $newEmail);
    }

    public function testEmailPseudonymizedIsDeterministic(): void
    {
        $pureEmail = 'test@example.com';
        $email1    = AnonymizeHelper::anonymizeEmail($pureEmail, true);
        $email2    = AnonymizeHelper::anonymizeEmail($pureEmail, true);
        $this->assertSame($email1, $email2, 'Pseudonymized emails should be deterministic');
    }

    public function testEmailAnonymizedIsNonDeterministic(): void
    {
        $pureEmail = 'test@example.com';
        $email1    = AnonymizeHelper::anonymizeEmail($pureEmail, false);
        // Small sleep to ensure different timestamp
        usleep(1000);
        $email2    = AnonymizeHelper::anonymizeEmail($pureEmail, false);
        $this->assertNotSame($email1, $email2, 'Anonymized emails should be non-deterministic');
    }

    public function testEmailEmptyString(): void
    {
        $newEmail = AnonymizeHelper::anonymizeEmail('');
        $this->assertEmpty($newEmail);
    }

    public function testEmailNull(): void
    {
        $newEmail = AnonymizeHelper::anonymizeEmail(null);
        $this->assertEmpty($newEmail);
    }

    public function testEmailBooleanFalse(): void
    {
        $newEmail = AnonymizeHelper::anonymizeEmail(false);
        $this->assertEmpty($newEmail);
    }

    public function testEmailWithLimitSmallerThanDomain(): void
    {
        $pureEmail = 'test@example.com';
        $limit     = 5; // Smaller than domain "@ano.nym" (8 chars)
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, false, $limit);

        // Should return full email because local part would be < 1 char
        $this->assertGreaterThan($limit, strlen($newEmail));
    }

    public function testEmailWithLimitEqualToDomain(): void
    {
        $pureEmail = 'test@example.com';
        $newDomain = 'ano.nym';
        $limit     = strlen('@'.$newDomain); // Exactly domain length
        $newEmail  = AnonymizeHelper::anonymizeEmail($pureEmail, false, $limit, $newDomain);

        // Should return full email because localPartLength would be 0
        $this->assertGreaterThan($limit, strlen($newEmail));
    }

    public function testVariousInvalidEmailFormats(): void
    {
        $invalidEmails = [
            'no-at-sign',
            '@no-local-part.com',
            'multiple@@at.com',
            'spaces in@email.com',
            'email@',
            '.email@domain.com',
            'email.@domain.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $result = AnonymizeHelper::anonymizeEmail($invalidEmail);
            $this->assertEmpty($result, "Email '{$invalidEmail}' should return empty string");
        }
    }

    public function testEmailWithVeryLongLocalPart(): void
    {
        $longLocalPart = str_repeat('a', 100);
        $email         = $longLocalPart.'@example.com';
        $newEmail      = AnonymizeHelper::anonymizeEmail($email);
        $this->assertEmpty($newEmail);
    }

    public function testEmailHashLength(): void
    {
        $email    = 'test@example.com';
        $newEmail = AnonymizeHelper::anonymizeEmail($email, false);

        $localPart = explode('@', $newEmail)[0];
        $this->assertEquals(64, strlen($localPart), 'SHA256 hash should be 64 characters');
    }

    // ===== Tests for anonymizeText() =====

    public function testTextPseudonymizedIsDeterministic(): void
    {
        $text  = 'Test Text';
        $hash1 = AnonymizeHelper::anonymizeText($text, true);
        $hash2 = AnonymizeHelper::anonymizeText($text, true);
        $this->assertSame($hash1, $hash2, 'Pseudonymized text should be deterministic');
    }

    public function testTextAnonymizedIsNonDeterministic(): void
    {
        $text  = 'Test Text';
        $hash1 = AnonymizeHelper::anonymizeText($text, false);
        usleep(1000);
        $hash2 = AnonymizeHelper::anonymizeText($text, false);
        $this->assertNotSame($hash1, $hash2, 'Anonymized text should be non-deterministic');
    }

    public function testTextWithLimit(): void
    {
        $text     = 'Test Text';
        $limit    = 32;
        $newText  = AnonymizeHelper::anonymizeText($text, false, $limit);

        $this->assertEquals($limit, strlen($newText));
        $this->assertNotSame($text, $newText);
    }

    public function testTextWithLimitEqualToHashLength(): void
    {
        $text    = 'Test Text';
        $limit   = 64; // SHA256 hash length
        $newText = AnonymizeHelper::anonymizeText($text, false, $limit);

        $this->assertEquals($limit, strlen($newText));
    }

    public function testTextWithLimitGreaterThanHashLength(): void
    {
        $text    = 'Test Text';
        $limit   = 100; // Greater than 64
        $newText = AnonymizeHelper::anonymizeText($text, false, $limit);

        // Should return full hash (64 chars) since limit > hash length
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextWithLimitZero(): void
    {
        $text    = 'Test Text';
        $newText = AnonymizeHelper::anonymizeText($text, false, 0);

        $this->assertEquals(64, strlen($newText), 'Limit 0 should return full hash');
    }

    public function testTextEmpty(): void
    {
        $newText = AnonymizeHelper::anonymizeText('');
        $this->assertNotEmpty($newText);
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextNull(): void
    {
        $newText = AnonymizeHelper::anonymizeText(null);
        $this->assertNotEmpty($newText);
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextBooleanFalse(): void
    {
        $newText = AnonymizeHelper::anonymizeText(false);
        $this->assertNotEmpty($newText);
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextHashLength(): void
    {
        $text    = 'Test Text';
        $newText = AnonymizeHelper::anonymizeText($text, false);
        $this->assertEquals(64, strlen($newText), 'SHA256 hash should be 64 characters');
    }

    public function testTextWithNumbers(): void
    {
        $text    = '12345';
        $newText = AnonymizeHelper::anonymizeText($text, false);
        $this->assertNotSame($text, $newText);
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextWithSpecialCharacters(): void
    {
        $text    = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $newText = AnonymizeHelper::anonymizeText($text, false);
        $this->assertNotSame($text, $newText);
        $this->assertEquals(64, strlen($newText));
    }

    public function testTextPseudonymizedWithLimit(): void
    {
        $text    = 'Test Text';
        $limit   = 20;
        $newText = AnonymizeHelper::anonymizeText($text, true, $limit);

        $this->assertEquals($limit, strlen($newText));
    }

    public function testConstants(): void
    {
        $this->assertEquals('ano.nym', AnonymizeHelper::PRE_DEFINED_DOMAIN);
        $this->assertEquals('pseudo.nym', AnonymizeHelper::PRE_PSEUDONYMIZED_DOMAIN);
    }
}
