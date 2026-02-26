<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testCloneResetPublishDates(): void
    {
        $email = new Email();
        $email->setPublishUp(new \DateTime());
        $email->setPublishDown(new \DateTime());
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPublishUp());
        $this->assertNull($emailClone->getPublishDown());
    }

    public function testCloneResetPlainText(): void
    {
        $email = new Email();
        $email->setPlainText('foo');
        $emailClone = clone $email;
        $this->assertNull($emailClone->getPlainText());
    }

    public function testGetPublishDownReturnsValueForSegmentEmailWithoutContinueSending(): void
    {
        $publishDown = new \DateTime('2026-12-31 23:59:00');
        $email       = new Email();
        $email->setEmailType('list');
        $email->setContinueSending(false);
        $email->setPublishDown($publishDown);
        $this->assertSame($publishDown, $email->getPublishDown());
    }

    #[DataProvider('setIsDuplicateDataProvider')]
    public function testIsDuplicate(bool $isDuplicate): void
    {
        $email = new Email();
        $email->setIsDuplicate($isDuplicate);
        Assert::assertIsBool($email->isDuplicate());
    }

    /**
     * @return iterable<array{bool}>
     */
    public static function setIsDuplicateDataProvider(): iterable
    {
        yield [true];
        yield [false];
    }
}
