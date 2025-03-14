<?php

namespace Mautic\ReportBundle\Tests\Form;

use Mautic\ReportBundle\Form\DataTransformer\ReportFilterDataTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ReportFilterDataTransformerTest extends TestCase
{
    public function testDateTypeKeepsOriginalFormat(): void
    {
        $columns = [
            'date_only' => ['type' => DateType::class],
            'datetime' => ['type' => DateTimeType::class],
        ];

        $transformer = new ReportFilterDataTransformer($columns);

        $dateOnlyValue = '2023-05-15';

        $filters = [
            ['column' => 'date_only', 'value' => $dateOnlyValue],
            ['column' => 'datetime', 'value' => $dateOnlyValue],
        ];

        $transformedFilters = $transformer->transform($filters);

        // DateType should preserve the original format without adding a time component
        $this->assertEquals($dateOnlyValue, $transformedFilters[0]['value'],
            'DateType should preserve the exact format without time component');
    }

    /**
     * Test that this change also works correctly in the reverseTransform method
     */
    public function testReverseTransformWithDateType(): void
    {
        $columns = [
            'date_column' => ['type' => DateType::class],
            'datetime_column' => ['type' => 'datetime'],
        ];

        $transformer = new ReportFilterDataTransformer($columns);

        $dateValue = '2023-05-15';
        $filters = [
            ['column' => 'date_column', 'value' => $dateValue],
            ['column' => 'datetime_column', 'value' => $dateValue],
        ];

        $transformedFilters = $transformer->reverseTransform($filters);

        // DateType should not be transformed in reverse transformation either
        $this->assertEquals($dateValue, $transformedFilters[0]['value'],
            'DateType should not be transformed in reverseTransform');
    }
}