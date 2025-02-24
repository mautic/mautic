<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Type;

use Doctrine\DBAL\Platforms\MySQL80Platform;
use Mautic\CoreBundle\Doctrine\Type\UTCDateTimeImmutableType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class UTCDateTimeImmutableTypeTest extends TestCase
{
    private string $previousTimeZone;
    private UTCDateTimeImmutableType $type;
    private MySQL80Platform $platform;

    protected function setUp(): void
    {
        $this->previousTimeZone = date_default_timezone_get();
        $this->type             = new UTCDateTimeImmutableType();
        $this->platform         = new MySQL80Platform();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimeZone);
    }

    public function testConvertToDatabaseValueWithNull(): void
    {
        Assert::assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    /**
     * @dataProvider timezoneProvider
     */
    public function testConvertToDatabaseValueWithDate(string $timezone, string $date): void
    {
        date_default_timezone_set($timezone);

        $databaseValue = $this->type->convertToDatabaseValue(new \DateTimeImmutable($date), $this->platform);

        Assert::assertIsString($databaseValue);
        Assert::assertSame($this->convertDateTimezone($date, $timezone, 'UTC'), $databaseValue, 'Database value should be converted to UTC.');
    }

    public function testConvertToPHPValueWithNull(): void
    {
        Assert::assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    /**
     * @dataProvider timezoneProvider
     */
    public function testConvertToPHPValueWithDate(string $timezone, string $date): void
    {
        date_default_timezone_set($timezone);

        $phpValue = $this->type->convertToPHPValue($date, $this->platform);

        Assert::assertInstanceOf(\DateTimeImmutable::class, $phpValue);
        Assert::assertSame($this->convertDateTimezone($date, 'UTC', $timezone), $phpValue->format(DateTimeHelper::FORMAT_DB), sprintf('PHP value should be converted to %s.', $timezone));
    }

    /**
     * @return iterable<string[]>
     */
    public function timezoneProvider(): iterable
    {
        yield ['America/Cayman', '2023-12-14 10:39:25'];
        yield ['Europe/Prague', '2022-11-18 18:32:27'];
        yield ['Indian/Maldives', '2018-01-01 02:02:55'];
        yield ['Asia/Taipei', '2019-03-01 13:09:45'];
    }

    private function convertDateTimezone(string $date, string $timezoneFrom, string $timezoneTo): string
    {
        return (new \DateTimeImmutable($date, new \DateTimeZone($timezoneFrom)))
            ->setTimezone(new \DateTimeZone($timezoneTo))
            ->format(DateTimeHelper::FORMAT_DB);
    }
}
