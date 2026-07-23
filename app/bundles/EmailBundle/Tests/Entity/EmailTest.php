<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testCloneResetPublishDates(): void
    {
        $email = new Email();
        $email->setPublishUp(new \DateTime());
        $email->setPublishDown(new \DateTime());
        $emailClone = clone $email;
        $this->assertNotInstanceOf(\DateTimeInterface::class, $emailClone->getPublishUp());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $emailClone->getPublishDown());
    }

    public function testCloneResetPlainText(): void
    {
        $email = new Email();
        $email->setPlainText('foo');
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPlainText());
    }

    #[DataProvider('setIsDuplicateDataProvider')]
    public function testIsDuplicate(bool $isDuplicate): void
    {
        $email = new Email();
        $email->setIsDuplicate($isDuplicate);
        $this->assertIsBool($email->isDuplicate());
    }

    /**
     * @return iterable<array{bool}>
     */
    public static function setIsDuplicateDataProvider(): iterable
    {
        yield [true];
        yield [false];
    }

    public function testGetSettingsReturnsEmptyArrayByDefault(): void
    {
        $email = new Email();

        $this->assertSame([], $email->getSettings());
    }

    public function testSetSettings(): void
    {
        $email    = new Email();
        $settings = ['subject' => 'Welcome', 'reply_to' => 'reply@example.com'];

        $this->assertSame($email, $email->setSettings($settings));
        $this->assertSame($settings, $email->getSettings());
    }

    public function testMagicSetterStoresPrefixedSetting(): void
    {
        $email                       = new Email();
        $email->settings_subjectline = 'Welcome!'; // @phpstan-ignore property.notFound

        $this->assertSame('Welcome!', $email->getSettings()['subjectline']);
    }

    public function testMagicGetterReadsPrefixedSetting(): void
    {
        $email = new Email();
        $email->setSettings(['subjectline' => 'Welcome!']);

        $this->assertSame('Welcome!', $email->settings_subjectline); // @phpstan-ignore property.notFound
    }

    public function testMagicGetterReturnsNullForUnknownOrUnsupportedFields(): void
    {
        $email = new Email();
        $email->setSettings(['subjectline' => 'Welcome!']);

        $this->assertNull($email->settings_non_existent); // @phpstan-ignore property.notFound
        $this->assertNull($email->subjectline); // @phpstan-ignore property.notFound
    }

    public function testMagicSetterIgnoresUnsupportedFields(): void
    {
        $email = new Email();
        $email->setSettings(['subjectline' => 'Welcome!']);
        $email->subjectline = 'Ignored'; // @phpstan-ignore property.notFound

        $this->assertSame(['subjectline' => 'Welcome!'], $email->getSettings());
    }
}
