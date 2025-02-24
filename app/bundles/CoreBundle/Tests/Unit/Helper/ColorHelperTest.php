<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\ColorHelper;

class ColorHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox The helper is instantiated correctly
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::__construct
     */
    public function testTheHelperIsInstantiatedWithoutAttributeCorrectly(): void
    {
        $helper = new ColorHelper();
        $this->assertEquals(0, $helper->getRed());
        $this->assertEquals(0, $helper->getGreen());
        $this->assertEquals(0, $helper->getBlue());
    }

    /**
     * @testdox A color hex hash can be set and the correct RGB representations filled
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::__construct
     */
    public function testThatColorHexAreSetCorrectly(): void
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
            $this->assertEquals($rgb[0], $helper->getRed());
            $this->assertEquals($rgb[1], $helper->getGreen());
            $this->assertEquals($rgb[2], $helper->getBlue());
        }
    }

    /**
     * @testdox A color hex hash can be set and received in the correct and valid hex format
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::setHex
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::toHex
     */
    public function testThatColorHexAreConvertedBackToHexCorrectly(): void
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
    public function testThatColorHexAreConvertedToRgbCorrectly(): void
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

    public function testThatRgbColorsAreReturnedCorrectly(): void
    {
        $colors = [
            '#ccc'    => [
                'red'   => 204,
                'green' => 204,
                'blue'  => 204,
            ],
            '#369'    => [
                'red'   => 51,
                'green' => 102,
                'blue'  => 153,
            ],
            '#f8Ac30' => [
                'red'   => 248,
                'green' => 172,
                'blue'  => 48,
            ],
        ];

        foreach ($colors as $hex => $colors) {
            $helper = new ColorHelper($hex);
            $this->assertEquals($colors['red'], $helper->getRed());
            $this->assertEquals($colors['green'], $helper->getGreen());
            $this->assertEquals($colors['blue'], $helper->getBlue());
        }
    }

    /**
     * @testdox A color hex hash can be set and received in the correct and valid rgba format
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::toRgba
     */
    public function testThatColorHexAreConvertedToRgbaCorrectly(): void
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
            $randA  = round(mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax(), 2);
            $this->assertEquals(sprintf($rgba, $randA), $helper->toRgba($randA));
        }
    }

    /**
     * @testdox The random color is within borders and not 0, 0, 0
     *
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::buildRandomColor
     * @covers \Mautic\CoreBundle\Helper\ColorHelper::getColorArray
     */
    public function testThatRandomColorIsWithinBorders(): void
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
