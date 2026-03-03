<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\ReportBundle\Form\DataTransformer\ReportFilterDataTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

final class ReportFilterDataTransformerTest extends TestCase
{
    private const LOCAL_TIMEZONE = 'Europe/Paris'; // UTC+2 during DST

    /**
     * @var array<string, array<string, string>>
     */
    private array $columns;

    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();

        // Force the timezone using reflection
        $reflection = new \ReflectionClass(DateTimeHelper::class);
        $property   = $reflection->getProperty('defaultLocalTimezone');
        $property->setValue(null, self::LOCAL_TIMEZONE);

        date_default_timezone_set(self::LOCAL_TIMEZONE);

        $this->columns = [
            'c.date_added'  => ['type' => 'datetime'],
            'c.test_time'   => ['type' => 'time'],
            'c.publish_up'  => ['type' => DateTimeType::class],
            'c.some_date'   => ['type' => DateType::class],
            'c.some_time'   => ['type' => TimeType::class],
            'c.email'       => ['type' => 'email'],
            'c.name'        => ['type' => 'text'],
        ];

        parent::setUp();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);

        // Reset the static property to null so it gets recalculated
        $reflection = new \ReflectionClass(DateTimeHelper::class);
        $property   = $reflection->getProperty('defaultLocalTimezone');
        $property->setValue(null, null);

        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTransformData')]
    public function testTransformUtcToLocalForSupportedTypes(string $column, string $utcValue, string $expectedLocalValue): void
    {
        // Arrange
        $transformer = new ReportFilterDataTransformer($this->columns);
        $filters     = [['column' => $column, 'condition' => 'eq', 'value' => $utcValue]];

        // Act
        $transformedFilters = $transformer->transform($filters);

        // Assert
        $this->assertSame($expectedLocalValue, $transformedFilters[0]['value']);
    }

    public static function provideTransformData(): \Generator
    {
        yield 'datetime type' => ['c.date_added', '2025-08-18 12:00:00', '2025-08-18 14:00:00'];
        yield 'DateTimeType class' => ['c.publish_up', '2025-08-18 12:00:00', '2025-08-18 14:00:00'];
        yield 'DateType class' => ['c.some_date', '2025-08-18 23:30:00', '2025-08-19']; // UTC->local can change the date
        yield 'time type' => ['c.test_time', '2025-08-18 12:00:00', '14:00:00'];
        yield 'TimeType class' => ['c.some_time', '2025-08-18 12:00:00', '14:00:00'];
    }

    public function testReverseTransformLocalToUtc(): void
    {
        // Arrange
        $transformer         = new ReportFilterDataTransformer($this->columns);
        $localDateTime       = '2025-08-18 14:00:00'; // In Europe/Paris (CEST)
        $expectedUtcDateTime = '2025-08-18 12:00:00';

        $filters = [['column' => 'c.date_added', 'condition' => 'eq', 'value' => $localDateTime]];

        // Act
        $reverseTransformedFilters = $transformer->reverseTransform($filters);

        // Assert
        $this->assertSame($expectedUtcDateTime, $reverseTransformedFilters[0]['value']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideStringConditions')]
    public function testTransformationIsSkippedForStringLikeConditions(string $condition): void
    {
        $transformer   = new ReportFilterDataTransformer($this->columns);
        $originalValue = '2025-08-18';
        $filters       = [['column' => 'c.date_added', 'condition' => $condition, 'value' => $originalValue]];

        $this->assertSame($originalValue, $transformer->transform($filters)[0]['value']);
        $this->assertSame($originalValue, $transformer->reverseTransform($filters)[0]['value']);
    }

    public static function provideStringConditions(): \Generator
    {
        yield ['like'];
        yield ['notLike'];
        yield ['startsWith'];
        yield ['endsWith'];
        yield ['contains'];
    }

    public function testReturnsOriginalFiltersIfColumnIsNotDefined(): void
    {
        $transformer = new ReportFilterDataTransformer($this->columns);
        $filters     = [
            ['column' => 'non.existent.column', 'condition' => 'eq', 'value' => 'some-value'],
        ];

        $this->assertSame($filters, $transformer->transform($filters));
        $this->assertSame($filters, $transformer->reverseTransform($filters));
    }
}
