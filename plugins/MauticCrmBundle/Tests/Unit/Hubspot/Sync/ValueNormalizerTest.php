<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\DTO\Field;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ValueNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueNormalizerTest extends TestCase
{
    /**
     * @param array<string, mixed> $property
     */
    #[DataProvider('propertyProvider')]
    public function testFieldTypeAndWritability(array $property, string $type, bool $writable): void
    {
        $field = Field::fromProperty(['name' => 'prop', 'label' => 'Prop', ...$property], false);

        $this->assertSame($type, $field->getType());
        $this->assertSame($writable, $field->isWritable());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, bool}>
     */
    public static function propertyProvider(): iterable
    {
        yield 'text' => [['type' => 'string', 'fieldType' => 'text'], NormalizedValueDAO::STRING_TYPE, true];
        yield 'number' => [['type' => 'number'], NormalizedValueDAO::FLOAT_TYPE, true];
        yield 'bool' => [['type' => 'bool', 'fieldType' => 'booleancheckbox'], NormalizedValueDAO::BOOLEAN_TYPE, true];
        yield 'date' => [['type' => 'date'], NormalizedValueDAO::DATE_TYPE, true];
        yield 'datetime' => [['type' => 'datetime'], NormalizedValueDAO::DATETIME_TYPE, true];
        yield 'select' => [['type' => 'enumeration', 'fieldType' => 'select'], NormalizedValueDAO::SELECT_TYPE, true];
        yield 'multiple checkboxes' => [['type' => 'enumeration', 'fieldType' => 'checkbox'], NormalizedValueDAO::MULTISELECT_TYPE, true];
        yield 'read only value' => [['type' => 'string', 'modificationMetadata' => ['readOnlyValue' => true]], NormalizedValueDAO::STRING_TYPE, false];
        yield 'calculated' => [['type' => 'number', 'calculated' => true], NormalizedValueDAO::FLOAT_TYPE, false];
    }

    public function testToMautic(): void
    {
        $normalizer = new ValueNormalizer();

        $this->assertSame('red|blue', $normalizer->toMautic($this->field(NormalizedValueDAO::MULTISELECT_TYPE), 'red;blue')->getNormalizedValue());
        $this->assertTrue($normalizer->toMautic($this->field(NormalizedValueDAO::BOOLEAN_TYPE), 'true')->getNormalizedValue());
        $this->assertSame(0, $normalizer->toMautic($this->field(NormalizedValueDAO::BOOLEAN_TYPE), 'false')->getNormalizedValue());
        $this->assertEqualsWithDelta(12.5, $normalizer->toMautic($this->field(NormalizedValueDAO::FLOAT_TYPE), '12.5')->getNormalizedValue(), PHP_FLOAT_EPSILON);
        $this->assertSame('', $normalizer->toMautic($this->field(NormalizedValueDAO::STRING_TYPE), null)->getNormalizedValue());
        $this->assertNull($normalizer->toMautic($this->field(NormalizedValueDAO::FLOAT_TYPE), '')->getNormalizedValue());

        $date = $normalizer->toMautic($this->field(NormalizedValueDAO::DATETIME_TYPE), '2026-11-12T10:30:00Z')->getNormalizedValue();
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertSame('2026-11-12 10:30:00', $date->format('Y-m-d H:i:s'));

        $timestamp = $normalizer->toMautic($this->field(NormalizedValueDAO::DATE_TYPE), '1793491200000')->getNormalizedValue();
        $this->assertInstanceOf(\DateTime::class, $timestamp);
        $this->assertSame('2026-11-01', $timestamp->format('Y-m-d'));
    }

    public function testToHubspot(): void
    {
        $normalizer = new ValueNormalizer();

        $this->assertSame('red;blue', $normalizer->toHubspot($this->field(NormalizedValueDAO::MULTISELECT_TYPE), new NormalizedValueDAO(NormalizedValueDAO::MULTISELECT_TYPE, 'red|blue', 'red|blue')));
        $this->assertSame('true', $normalizer->toHubspot($this->field(NormalizedValueDAO::BOOLEAN_TYPE), new NormalizedValueDAO(NormalizedValueDAO::BOOLEAN_TYPE, 1, true)));
        $this->assertSame('false', $normalizer->toHubspot($this->field(NormalizedValueDAO::BOOLEAN_TYPE), new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, '0', '0')));
        $this->assertSame('', $normalizer->toHubspot($this->field(NormalizedValueDAO::STRING_TYPE), new NormalizedValueDAO(NormalizedValueDAO::STRING_TYPE, null)));
        $this->assertSame('12.5', $normalizer->toHubspot($this->field(NormalizedValueDAO::FLOAT_TYPE), new NormalizedValueDAO(NormalizedValueDAO::FLOAT_TYPE, '12.5', 12.5)));

        $dateTime = new \DateTime('2026-11-12 12:30:00', new \DateTimeZone('Europe/Tallinn'));
        $this->assertSame('2026-11-12T10:30:00Z', $normalizer->toHubspot($this->field(NormalizedValueDAO::DATETIME_TYPE), new NormalizedValueDAO(NormalizedValueDAO::DATETIME_TYPE, $dateTime, $dateTime)));
        $this->assertSame('2026-11-12', $normalizer->toHubspot($this->field(NormalizedValueDAO::DATE_TYPE), new NormalizedValueDAO(NormalizedValueDAO::DATE_TYPE, $dateTime, $dateTime)));
    }

    private function field(string $type): Field
    {
        return new Field('prop', 'Prop', $type, false, true);
    }
}
