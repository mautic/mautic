<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Mautic\LeadBundle\Form\DataTransformer\FieldFilter;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Segment\RelativeDate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FieldFilterTransformerTest extends TestCase
{
    private FieldFilterTransformer $transformer;

    /**
     * @var MockObject&RelativeDate
     */
    private MockObject $relativeDate;

    protected function setUp(): void
    {
        parent::setUp();

        $serviceLocator = new ServiceLocator([
            'date'     => fn () => new FieldFilter\FieldFilterDateTransformer(),
            'datetime' => fn () => new FieldFilter\FieldFilterDateTimeTransformer($this->createMock(TranslatorInterface::class)),
        ]);
        $this->transformer = new FieldFilterTransformer($serviceLocator);
        $this->transformer->setContext('segment');
        $translator         = $this->createMock(TranslatorInterface::class);
        $this->relativeDate = $this->createMock(RelativeDate::class);
        $translator
            ->method('trans')
            ->willReturnCallback(fn (string $id, array $parameters, ?string $domain, ?string $locale): string => match ($id) {
                'mautic.lead.list.month_last'  => isset($locale) ? 'last month' : 'letzter Monat',
                'mautic.lead.list.month_next'  => isset($locale) ? 'next month' : 'nächster Monat',
                'mautic.lead.list.month_this'  => isset($locale) ? 'this month' : 'dieser Monat',
                'mautic.lead.list.tomorrow'    => isset($locale) ? 'tomorrow' : 'morgen',
                'mautic.lead.list.yesterday'   => isset($locale) ? 'yesterday' : 'gestern',
                'mautic.lead.list.week_last'   => isset($locale) ? 'last week' : 'letzte Woche',
                'mautic.lead.list.week_next'   => isset($locale) ? 'next week' : 'nächste Woche',
                'mautic.lead.list.week_this'   => isset($locale) ? 'this week' : 'diese Woche',
                'mautic.lead.list.year_last'   => isset($locale) ? 'last year' : 'letztes Jahr',
                'mautic.lead.list.year_next'   => isset($locale) ? 'next year' : 'nächstes Jahr',
                'mautic.lead.list.year_this'   => isset($locale) ? 'this year' : 'dieses Jahr',
                'mautic.lead.list.birthday'    => isset($locale) ? 'birthday' : 'dieses Jahr',
                'mautic.lead.list.anniversary' => isset($locale) ? 'anniversary' : 'Jahrestag',
                default                        => isset($locale) ? 'today' : 'heute',
            });
        $this->transformer  = new FieldFilterTransformer($translator, $this->relativeDate);
    }

    public function testTransform(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => '2020-03-17 17:22:34',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => [
                            'absoluteDate' => '2020-03-17 17:22',
                        ],
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
                        'filter' => [
                            'absoluteDate' => '2020-03-17 17:22',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransform(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => '2020-03-17 17:22:34',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => [
                            'absoluteDate' => '2020-03-17 17:22',
                        ],
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
                        'filter' => [
                            'absoluteDate' => '2020-03-17 17:22',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testTransformWithAbsoluteDateFilter(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter'   => [
                        'dateTypeMode'             => 'absolute',
                        'absoluteDate'             => '2020-03-17 17:22:34',
                        'relativeDateInterval'     => '1',
                        'relativeDateIntervalUnit' => 'day',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => [
                            'dateTypeMode'             => 'absolute',
                            'absoluteDate'             => '2020-03-17 17:22',
                            'relativeDateInterval'     => '1',
                            'relativeDateIntervalUnit' => 'day',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testTransformWithRelativeDateFilter(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter'   => [
                        'dateTypeMode'             => 'relative',
                        'absoluteDate'             => null,
                        'relativeDateInterval'     => '1',
                        'relativeDateIntervalUnit' => 'day',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => [
                            'dateTypeMode'             => 'relative',
                            'absoluteDate'             => null,
                            'relativeDateInterval'     => '1',
                            'relativeDateIntervalUnit' => 'day',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransformWithAbsoluteDate(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter'   => [
                        'dateTypeMode'             => 'absolute',
                        'absoluteDate'             => '2020-03-17 17:22:34',
                        'relativeDateInterval'     => '1',
                        'relativeDateIntervalUnit' => 'day',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter'   => [
                            'dateTypeMode'             => 'absolute',
                            'absoluteDate'             => '2020-03-17 17:22',
                            'relativeDateInterval'     => '1',
                            'relativeDateIntervalUnit' => 'day',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testReverseTransformWithRelativeDate(): void
    {
        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter'   => [
                        'dateTypeMode'             => 'relative',
                        'absoluteDate'             => null,
                        'relativeDateInterval'     => '2',
                        'relativeDateIntervalUnit' => 'day',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter'   => [
                            'dateTypeMode'             => 'relative',
                            'absoluteDate'             => null,
                            'relativeDateInterval'     => '2',
                            'relativeDateIntervalUnit' => 'day',
                        ],
                    ],
                ],
            ],
            $filters
        );
    }

    public function testRelativeTransform(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => 'today',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => 'heute',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testRelativeTransformWithBcFilter(): void
    {
        $filters = $this->transformer->transform([
            [
                'type'   => 'datetime',
                'filter' => 'today',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => 'today',
                    'properties' => [
                        'filter' => 'heute',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testRelativeReverseTransform(): void
    {
        $this->relativeDate->expects($this->once())
            ->method('getRelativeDateStrings')
            ->willReturn([
                'mautic.lead.list.today' => 'heute',
            ]);

        $filters = $this->transformer->reverseTransform([
            [
                'type'       => 'datetime',
                'properties' => [
                    'filter' => 'heute',
                ],
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'properties' => [
                        'filter' => 'today',
                    ],
                ],
            ],
            $filters
        );
    }

    public function testRelativeReverseTransformWithBcFilter(): void
    {
        $this->relativeDate->expects($this->once())
            ->method('getRelativeDateStrings')
            ->willReturn([
                'mautic.lead.list.today' => 'heute',
            ]);

        $filters = $this->transformer->reverseTransform([
            [
                'type'   => 'datetime',
                'filter' => 'heute',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'type'       => 'datetime',
                    'filter'     => 'heute',
                    'properties' => [
                        'filter' => 'today',
                    ],
                ],
            ],
            $filters
        );
    }
}
