<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use PHPUnit\Framework\Assert;

#[\PHPUnit\Framework\Attributes\CoversClass(AbstractFormFieldHelper::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\CoreBundle\Helper\ListParser\BarListParser::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\CoreBundle\Helper\ListParser\JsonListParser::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\CoreBundle\Helper\ListParser\ValueListParser::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\CoreBundle\Helper\ListParser\ArrayListParser::class)]
class AbstractFormFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testBarFormatConvertedToArray(): void
    {
        $this->assertEquals(
            [
                'value1' => 'value1',
                'value2' => 'value2',
                'value3' => 'value3',
            ],
            AbstractFormFieldHelper::parseList('value1|value2|value3')
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testBarLabelValueFormatConvertedToArray(): void
    {
        $this->assertEquals(
            [
                'value1' => 'label1',
                'value2' => 'label2',
                'value3' => 'label3',
            ],
            AbstractFormFieldHelper::parseList('label1|label2|label3||value1|value2|value3')
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testJsonEncodedFormatConvertedToArray(): void
    {
        $this->assertEquals(
            [
                'value1' => 'label1',
                'value2' => 'label2',
                'value3' => 'label3',
            ],
            AbstractFormFieldHelper::parseList('{"value1":"label1","value2":"label2","value3":"label3"}')
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testSingleSelectedValueDoesNotGoIntoJson(): void
    {
        $this->assertEquals(['1' => '1'], AbstractFormFieldHelper::parseList('1'));
    }

    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testLabelValuePairsAreFlattened(): void
    {
        $this->assertEquals(
            [
                'value1' => 'label1',
                'value2' => 'label2',
                'value3' => 'label3',
            ],
            AbstractFormFieldHelper::parseList(
                [
                    [
                        'label' => 'label1',
                        'value' => 'value1',
                    ],
                    [
                        'label' => 'label2',
                        'value' => 'value2',
                    ],
                    [
                        'label' => 'label3',
                        'value' => 'value3',
                    ],
                ]
            )
        );
    }

    /**
     * @param mixed[] $inputOptions
     * @param mixed[] $expectedOptions
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideChoices')]
    public function testParseList(array $inputOptions, array $expectedOptions): void
    {
        $this->assertEquals($expectedOptions, AbstractFormFieldHelper::parseList($inputOptions));
    }

    /**
     * @return iterable<array<mixed[]>>
     */
    public static function provideChoices(): iterable
    {
        yield [
            [
                ['value' => null, 'label' => null],
            ],
            [],
        ];

        yield [
            [
                ['value' => 0, 'label' => 0],
            ],
            [0 => '0'],
        ];

        yield [
            [
                ['value' => '', 'label' => ''],
            ],
            [],
        ];

        yield [
            [
                ['value' => 'one', 'label' => 'One'],
            ],
            ['one' => 'One'],
        ];

        yield [
            ['one' => 'One'],
            ['one' => 'One'],
        ];

        yield [
            ['' => ''],
            [],
        ];

        yield [
            [null => null],
            [],
        ];

        yield [
            [0 => 0],
            [0 => '0'],
        ];
    }

    public function testparseChoiceListWithNullValue(): void
    {
        Assert::assertEquals(
            [0 => 'label4'],
            AbstractFormFieldHelper::parseList(
                [
                    [
                        'label' => 'label1',
                        'value' => '',
                    ],
                    [
                        'label' => 'label2',
                        'value' => null,
                    ],
                    [
                        'label' => 'label3',
                        'value' => 0,
                    ],
                    [
                        'label' => 'label4',
                        'value' => '0',
                    ],
                ]
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('The string is parsed correctly into a choice array')]
    public function testLabelValuePairsAreFlattenedWithOptGroup(): void
    {
        $array['optGroup1'] = [
            [
                'label' => 'label1',
                'value' => 'value1',
            ],
            [
                'label' => 'label2',
                'value' => 'value2',
            ],
            [
                'label' => 'label3',
                'value' => 'value3',
            ],
        ];
        $array['optGroup2'] = [
            [
                'label' => 'label1',
                'value' => 'value1',
            ],
            [
                'label' => 'label2',
                'value' => 'value2',
            ],
            [
                'label' => 'label3',
                'value' => 'value3',
            ],
        ];
        $expected = [
            'optGroup1' => [
                'value1' => 'label1',
                'value2' => 'label2',
                'value3' => 'label3',
            ],
            'optGroup2' => [
                'value1' => 'label1',
                'value2' => 'label2',
                'value3' => 'label3',
            ],
        ];
        $actual = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }

    public function testNumericalArrayConvertedToKeyLabelPairs(): void
    {
        $array = [
            'value1',
            'value2',
            'value3',
        ];

        $expected = [
            'value1' => 'value1',
            'value2' => 'value2',
            'value3' => 'value3',
        ];
        $actual = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }

    public function testBooleanArrayList(): void
    {
        $array = [
            0 => 'no',
            1 => 'yes',
        ];

        $expected = [
            0 => 'no',
            1 => 'yes',
        ];

        $actual = AbstractFormFieldHelper::parseBooleanList($array);

        $this->assertEquals($expected, $actual);
    }

    public function testBooleanBarStringList(): void
    {
        $string   = 'no|yes||0|1';
        $expected = [
            0 => 'no',
            1 => 'yes',
        ];

        $actual = AbstractFormFieldHelper::parseBooleanList($string);

        $this->assertEquals($expected, $actual);
    }

    public function testBooleanJsonStringList(): void
    {
        $string   = '["no", "yes"]';
        $expected = [
            0 => 'no',
            1 => 'yes',
        ];

        $actual = AbstractFormFieldHelper::parseBooleanList($string);

        $this->assertEquals($expected, $actual);
    }

    public function testNumericalJsonStringList(): void
    {
        $string   = '["no", "yes"]';
        $expected = [
            'no'  => 'no',
            'yes' => 'yes',
        ];

        $actual = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }
}
