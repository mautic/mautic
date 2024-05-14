<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Mautic\LeadBundle\Form\DataTransformer\FieldFilter;
use Mautic\LeadBundle\Form\DataTransformer\FieldFilterTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FieldFilterTransformerTest extends TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    private FieldFilterTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $serviceLocator = new ServiceLocator([
            'date'     => fn () => new FieldFilter\FieldFilterDateTransformer(),
            'datetime' => fn () => new FieldFilter\FieldFilterDateTimeTransformer($this->createMock(TranslatorInterface::class)),
        ]);
        $this->transformer = new FieldFilterTransformer($serviceLocator);
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
                            'absoluteDate' => '2020-03-17 18:22',
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
                            'absoluteDate' => '2020-03-17 18:22',
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
}
