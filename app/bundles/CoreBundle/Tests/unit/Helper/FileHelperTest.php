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

use Mautic\CoreBundle\Helper\FileHelper;

class FileHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Conversion of Bytes to Megebytes
     *
     * @covers       \Mautic\CoreBundle\Helper\FileHelper::convertBytesToMegabytes
     *
     * @dataProvider bytesToMegabytesProvider
     *
     * @param int   $byte
     * @param float $megabyte
     */
    public function testConversionFromBytesToMegabytes($byte, $megabyte)
    {
        $fileHelper = new FileHelper();

        $this->assertSame($megabyte, $fileHelper::convertBytesToMegabytes($byte));
    }

    public function bytesToMegabytesProvider()
    {
        return [
            [0, 0.0],
            [1048576, 1.0],
            [10485760, 10.0],
            [-10485760, -10.0],
        ];
    }

    /**
     * @testdox Conversion of Megebytes to Bytes
     *
     * @covers       \Mautic\CoreBundle\Helper\FileHelper::convertMegabytesToBytes
     *
     * @dataProvider megabytesToBytesProvider
     *
     * @param int $megabyte
     * @param int $byte
     */
    public function testConversionFromMegabytesToBytes($megabyte, $byte)
    {
        $fileHelper = new FileHelper();

        $this->assertSame($byte, $fileHelper::convertMegabytesToBytes($megabyte));
    }

    public function megabytesToBytesProvider()
    {
        return [
            [0, 0],
            [1, 1048576],
            [5, 5242880],
        ];
    }
}
