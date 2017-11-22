<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;

class AbstractFormFieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox The string is parsed correctly into a choise array
     *
     * @covers \Mautic\CoreBundle\\Helper\AbstractFormFieldHelper::parseList
     */
    public function testBarFormatConvertedToArray()
    {
        $string   = 'value1|value2|value3';
        $expected = [
            'value1' => 'value1',
            'value2' => 'value2',
            'value3' => 'value3',
        ];
        $actual = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choise array
     *
     * @covers \Mautic\CoreBundle\\Helper\AbstractFormFieldHelper::parseList
     */
    public function testBarLabelValueFormatConvertedToArray()
    {
        $string   = 'label1|label2|label3||value1|value2|value3';
        $expected = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $actual = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choise array
     *
     * @covers \Mautic\CoreBundle\\Helper\AbstractFormFieldHelper::parseList
     */
    public function testJsonEncodedFormatConvertedToArray()
    {
        $string   = '{"value1":"label1","value2":"label2","value3":"label3"}';
        $expected = [
            'value1' => 'label1',
            'value2' => 'label2',
            'value3' => 'label3',
        ];
        $actual = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choise array
     *
     * @covers \Mautic\CoreBundle\\Helper\AbstractFormFieldHelper::parseList
     */
    public function testSingleSelectedValueDoesNotGoIntoJson()
    {
        $string   = '1';
        $expected = [
            '1' => '1',
        ];
        $actual = AbstractFormFieldHelper::parseList($string);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox The string is parsed correctly into a choise array
     *
     * @covers \Mautic\CoreBundle\\Helper\AbstractFormFieldHelper::parseList
     */
    public function testLabelValuePairsAreFlattened()
    {
        $array = [
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
        $actual = AbstractFormFieldHelper::parseList($array);

        $this->assertEquals($expected, $actual);
    }
}
