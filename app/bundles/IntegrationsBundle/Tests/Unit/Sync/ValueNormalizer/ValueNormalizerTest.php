<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\ValueNormalizer;

use DateTimeInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use PHPUnit\Framework\TestCase;

class ValueNormalizerTest extends TestCase
{
    public function testNullDateTimeValue(): void
    {
        $valueNormalizer    = new ValueNormalizer();
        $normalizedValueDAO = $valueNormalizer->normalizeForMautic(NormalizedValueDAO::DATETIME_TYPE, null);

        $this->assertNull($normalizedValueDAO->getNormalizedValue());
        $this->assertNull($normalizedValueDAO->getOriginalValue());
    }

    public function testNotNullDateTimeValue(): void
    {
        $valueNormalizer    = new ValueNormalizer();
        $normalizedValueDAO = $valueNormalizer->normalizeForMautic(NormalizedValueDAO::DATETIME_TYPE, '2019-10-08');

        $this->assertInstanceOf(DateTimeInterface::class, $normalizedValueDAO->getNormalizedValue());
        $this->assertSame('2019-10-08', $normalizedValueDAO->getNormalizedValue()->format('Y-m-d'));
        $this->assertSame('2019-10-08', $normalizedValueDAO->getOriginalValue());
    }
}
