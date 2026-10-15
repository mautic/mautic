<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Type;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Mautic\CoreBundle\Doctrine\Type\UTCDateTimeType;
use PHPUnit\Framework\TestCase;

final class UTCDateTimeTypeTest extends TestCase
{
    public function testMutableAndImmutableDatesAreStoredInUtcWithoutChangingInput(): void
    {
        $type = new UTCDateTimeType();
        foreach ([\DateTime::class, \DateTimeImmutable::class] as $dateClass) {
            $date = new $dateClass('2026-01-02 03:04:05', new \DateTimeZone('America/Sao_Paulo'));

            $this->assertSame('2026-01-02 06:04:05', $type->convertToDatabaseValue($date, new PostgreSQLPlatform()));
            $this->assertSame('2026-01-02 03:04:05', $date->format('Y-m-d H:i:s'));
            $this->assertSame('America/Sao_Paulo', $date->getTimezone()->getName());
        }
    }
}
