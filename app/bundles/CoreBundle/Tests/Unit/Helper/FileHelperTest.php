<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\FileHelper;

class FileHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Conversion of Bytes to Megebytes
     *
     * @covers       \Mautic\CoreBundle\Helper\FileHelper::convertBytesToMegabytes
     *
     * @dataProvider bytesToMegabytesProvider
     */
    public function testConversionFromBytesToMegabytes(int $byte, float $megabyte)
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
     */
    public function testConversionFromMegabytesToBytes(int $megabyte, int $byte)
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

    /**
     * @testdox Conversion of PHP size to Bytes
     *
     * @covers       \Mautic\CoreBundle\Helper\FileHelper::convertPHPSizeToBytes
     *
     * @dataProvider phpSizeToBytesProvider
     */
    public function testConvertPHPSizeToBytes(string $phpSize, int $bytes)
    {
        $fileHelper = new FileHelper();

        $this->assertSame($bytes, $fileHelper::convertPHPSizeToBytes($phpSize));
    }

    public function phpSizeToBytesProvider()
    {
        return [
            ['3048M', 3196059648],
            ['127M', 133169152],
            ['1k', 1024],
            ['1K ', 1024],
            ['1M', 1048576],
            ['1G', 1073741824],
            ['1P', 1125899906842624],
            ['1024', 1024],
        ];
    }
}
