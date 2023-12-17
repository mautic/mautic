<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use Mautic\LeadBundle\Segment\RelativeDate;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FieldFilterTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    private \Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer $transformer;

    /**
     * @var MockObject|RelativeDate
     */
    private $relativeDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator   = $this->createMock(TranslatorInterface::class);
        $this->relativeDate = $this->createMock(RelativeDate::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, $parameters, $domain, $locale): string {
                return match ($id) {
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
                };
            });
        $this->transformer  = new FieldFilterTransformer($this->translator, $this->relativeDate);
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
                        'filter' => '2020-03-17 18:22',
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
                        'filter' => '2020-03-17 18:22',
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
                        'filter' => '2020-03-17 17:22',
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
