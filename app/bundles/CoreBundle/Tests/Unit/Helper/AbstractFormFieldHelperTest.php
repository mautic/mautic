<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;

class AbstractFormFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\BarListParser::parse
     */
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

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\BarListParser::parse
     */
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

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\JsonListParser::parse
     */
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

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ValueListParser::parse
     */
    public function testSingleSelectedValueDoesNotGoIntoJson(): void
    {
        $this->assertEquals(['1' => '1'], AbstractFormFieldHelper::parseList('1'));
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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
     * @dataProvider provideChoices
     *
     * @param mixed[] $inputOptions
     * @param mixed[] $expectedOptions
     */
    public function testParseList(array $inputOptions, array $expectedOptions): void
    {
        $this->assertEquals($expectedOptions, AbstractFormFieldHelper::parseList($inputOptions));
    }

    /**
     * @return iterable<array<mixed[]>>
     */
    public function provideChoices(): iterable
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

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\BarListParser::parse
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\JsonListParser::parse
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\JsonListParser::parse
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
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
