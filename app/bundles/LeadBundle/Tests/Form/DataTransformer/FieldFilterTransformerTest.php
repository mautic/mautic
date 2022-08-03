<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

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

        $this->data = [
            'test 1' => [
                'input'    => '2020-03-17 17:22:34',
                'expected' => '2020-03-17 17:22',
            ],
            'test 2' => [
                'input'    => '2 days ago',
                'expected' => '2 days ago',
            ],
            'test 3' => [
                'input'    => 'first day of August 2022',
                'expected' => 'first day of August 2022',
            ],
        ];
    }

    public function testTransform(): void
    {
        foreach ($this->data as $item) {
            $filters = $this->transformer->transform([
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => $item['input'],
                    ],
                ],
            ]);

            $this->assertSame(
                [
                    [
                        'type'       => 'datetime',
                        'properties' => [
                            'filter' => $item['expected'],
                        ],
                    ],
                ],
                $filters
            );
        }
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

    public function testReverseTransform(): void
    {
        foreach ($this->data as $item) {
            $filters = $this->transformer->reverseTransform([
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => $item['input'],
                    ],
                ],
            ]);

            $this->assertSame(
                [
                    [
                        'type'       => 'datetime',
                        'properties' => [
                            'filter' => $item['expected'],
                        ],
                    ],
                ],
                $filters
            );
        }
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
}
