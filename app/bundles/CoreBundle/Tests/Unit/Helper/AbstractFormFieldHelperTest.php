<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    public function testBarFormatConvertedToArray()
    {
        $string   = 'value1|value2|value3';
        $expected = [
            'value1' => 'value1',
            'value2' => 'value2',
            'value3' => 'value3',
        ];
        $actual   = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\BarListParser::parse
     */
    public function testBarLabelValueFormatConvertedToArray()
    {
        $string   = 'label1|label2|label3||value1|value2|value3';
        $expected = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $actual   = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\JsonListParser::parse
     */
    public function testJsonEncodedFormatConvertedToArray()
    {
        $string   = '{"value1":"label1","value2":"label2","value3":"label3"}';
        $expected = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $actual   = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ValueListParser::parse
     */
    public function testSingleSelectedValueDoesNotGoIntoJson()
    {
        $string   = '1';
        $expected = [
            '1' => '1',
        ];
        $actual   = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
    public function testLabelValuePairsAreFlattened()
    {
        $array    = [
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
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $actual   = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choice array
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
    public function testLabelValuePairsAreFlattenedWithOptGroup()
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
        $expected           = [
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
        $actual             = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
    public function testNumericalArrayConvertedToKeyLabelPairs()
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
        $actual   = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers  \Mautic\CoreBundle\Helper\ListParser\ArrayListParser::parse
     */
    public function testBooleanArrayList()
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
    public function testBooleanBarStringList()
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
    public function testBooleanJsonStringList()
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
    public function testNumericalJsonStringList()
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
