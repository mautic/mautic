<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\ColorHelper;

/**
 * Class ColorHelper test.
 */
class ColorHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox The helper is instantiated correctly
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::__construct
     */
    public function testTheHelperIsInstantiatedWithoutAttributeCorrectly()
    {
        $helper = new ColorHelper();
        $this->assertAttributeSame(0, 'red', $helper);
        $this->assertAttributeSame(0, 'green', $helper);
        $this->assertAttributeSame(0, 'blue', $helper);
    }

    /**
     * @testdox A color hex hash can be set and the correct RGB representations filled
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::__construct
     */
    public function testThatColorHexAreSetCorrectly()
    {
        $colors = [
            '#ccc'    => [204, 204, 204],
            '#fff'    => [255, 255, 255],
            '#000'    => [0, 0, 0],
            '#333333' => [51, 51, 51],
            '#369'    => [51, 102, 153],
            '#f8Ac30' => [248, 172, 48],
        ];

        foreach ($colors as $hex => $rgb) {
            $helper = new ColorHelper($hex);
            $this->assertAttributeSame($rgb[0], 'red', $helper);
            $this->assertAttributeSame($rgb[1], 'green', $helper);
            $this->assertAttributeSame($rgb[2], 'blue', $helper);
        }
    }

    /**
     * @testdox A color hex hash can be set and received in the correct and valid hex format
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::setHex
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::toHex
     */
    public function testThatColorHexAreConvertedBackToHexCorrectly()
    {
        $colors = [
            '#ccc'    => '#cccccc',
            '#fff'    => '#ffffff',
            '#000'    => '#000000',
            '#333333' => '#333333',
            '#369'    => '#336699',
            '#f8Ac30' => '#f8ac30',
        ];

        foreach ($colors as $hex1 => $hex2) {
            $helper = new ColorHelper();
            $helper->setHex($hex1);
            $this->assertEquals($hex2, $helper->toHex());
        }
    }

    /**
     * @testdox A color hex hash can be set and received in the correct and valid rgb format
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::toRgb
     */
    public function testThatColorHexAreConvertedToRgbCorrectly()
    {
        $colors = [
            '#ccc'    => 'rgb(204,204,204)',
            '#fff'    => 'rgb(255,255,255)',
            '#000'    => 'rgb(0,0,0)',
            '#333333' => 'rgb(51,51,51)',
            '#369'    => 'rgb(51,102,153)',
            '#f8Ac30' => 'rgb(248,172,48)',
        ];

        foreach ($colors as $hex => $rgb) {
            $helper = new ColorHelper($hex);
            $this->assertEquals($rgb, $helper->toRgb());
        }
    }

    /**
     * @testdox A color hex hash can be set and received in the correct and valid rgba format
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::toRgba
     */
    public function testThatColorHexAreConvertedToRgbaCorrectly()
    {
        $colors = [
            '#ccc'    => 'rgba(204,204,204,%g)',
            '#fff'    => 'rgba(255,255,255,%g)',
            '#000'    => 'rgba(0,0,0,%g)',
            '#333333' => 'rgba(51,51,51,%g)',
            '#369'    => 'rgba(51,102,153,%g)',
            '#f8Ac30' => 'rgba(248,172,48,%g)',
        ];

        foreach ($colors as $hex => $rgba) {
            $helper = new ColorHelper($hex);
            $randA  = round((mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()), 2);
            $this->assertEquals(sprintf($rgba, $randA), $helper->toRgba($randA, $randA));
        }
    }

    /**
     * @testdox The random color is within borders and not 0, 0, 0
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::buildRandomColor
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::getColorArray
     */
    public function testThatRandomColorIsWithinBorders()
    {
        $helper = new ColorHelper();
        $helper->buildRandomColor();
        $rgb = $helper->getColorArray();

        $this->assertGreaterThan(0, $rgb[0]);
        $this->assertGreaterThan(0, $rgb[1]);
        $this->assertGreaterThan(0, $rgb[2]);

        $this->assertLessThanOrEqual(256, $rgb[0]);
        $this->assertLessThanOrEqual(256, $rgb[1]);
        $this->assertLessThanOrEqual(256, $rgb[2]);
    }
}
