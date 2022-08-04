<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Generator;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Symfony\Component\Translation\TranslatorInterface;

final class FieldFilterTransformerTest extends \PHPUnit\Framework\TestCase
{
    private FieldFilterTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->withConsecutive(
                ['mautic.lead.list.month_last'],
                ['mautic.lead.list.month_next'],
                ['mautic.lead.list.month_this'],
                ['mautic.lead.list.today'],
                ['mautic.lead.list.tomorrow'],
                ['mautic.lead.list.yesterday'],
                ['mautic.lead.list.week_last'],
                ['mautic.lead.list.week_next'],
                ['mautic.lead.list.week_this'],
                ['mautic.lead.list.year_last'],
                ['mautic.lead.list.year_next'],
                ['mautic.lead.list.year_this'],
                ['mautic.lead.list.anniversary'],
                ['mautic.lead.list.ago'],
                ['mautic.lead.list.first_day_of'],
                ['mautic.lead.list.last_day_of']
            )
            ->willReturnOnConsecutiveCalls(
                'this month',
                'today',
                'tomorrow',
                'yesterday',
                'last week',
                'next week',
                'this week',
                'last year',
                'next year',
                'this year',
                'anniversary',
                'ago',
                'first day of',
                'last day of',
            );

        $this->transformer = new FieldFilterTransformer($translator);
    }

    /**
     * @dataProvider dateProvider
     */
    public function testTransform(string $value, string $expected): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => $value,
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => $expected,
                    ],
                ],
            ],
            $filters
        );
    }

    public function testTransformWithBcFilter(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'   => 'datetime',
                'filter' => '2020-03-17 17:22:34',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => '2020-03-17 17:22:34',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }

    /**
     * @dataProvider dateProvider
     */
    public function testReverseTransform(string $value, string $expected): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => $value,
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => $expected,
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransformWithBcFilter(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'   => 'datetime',
                'filter' => '2020-03-17 17:22:34',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => '2020-03-17 17:22:34',
                    'properties' => [
                        'filter' => '2020-03-17 17:22',
                    ],
                ],
            ],
            $filters
        );
    }

    /**
     * @return Generator<array<string, string>>
     */
    public function dateProvider(): Generator
    {
        yield ['2020-03-17 17:22:34', '2020-03-17 17:22'];
        yield ['2 days ago', '2 days ago'];
        yield ['first day of August 2022', 'first day of August 2022'];
    }
}
