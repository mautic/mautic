<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\FileHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(FileHelper::class)]
final class FileHelperTest extends \PHPUnit\Framework\TestCase
{
    #[DataProvider('bytesToMegabytesProvider')]
    #[TestDox('Conversion of Bytes to Megebytes')]
    public function testConversionFromBytesToMegabytes(int $byte, float $megabyte): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($megabyte, $fileHelper::convertBytesToMegabytes($byte));
    }

    /**
     * @return \Iterator<int, array{int, float}>
     */
    public static function bytesToMegabytesProvider(): \Iterator
    {
        yield [0, 0.0];
        yield [1_048_576, 1.0];
        yield [10_485_760, 10.0];
        yield [-10_485_760, -10.0];
    }

    #[DataProvider('megabytesToBytesProvider')]
    #[TestDox('Conversion of Megebytes to Bytes')]
    public function testConversionFromMegabytesToBytes(int $megabyte, int $byte): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($byte, $fileHelper::convertMegabytesToBytes($megabyte));
    }

    /**
     * @return \Iterator<int, array{int, int}>
     */
    public static function megabytesToBytesProvider(): \Iterator
    {
        yield [0, 0];
        yield [1, 1_048_576];
        yield [5, 5_242_880];
    }

    #[DataProvider('phpSizeToBytesProvider')]
    #[TestDox('Conversion of PHP size to Bytes')]
    public function testConvertPHPSizeToBytes(string $phpSize, int $bytes): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($bytes, $fileHelper::convertPHPSizeToBytes($phpSize));
    }

    /**
     * @return \Iterator<int, array{string, int}>
     */
    public static function phpSizeToBytesProvider(): \Iterator
    {
        yield ['3048M', 3_196_059_648];
        yield ['127M', 133_169_152];
        yield ['1k', 1024];
        yield ['1K ', 1024];
        yield ['1M', 1_048_576];
        yield ['1G', 1_073_741_824];
        yield ['1P', 1_125_899_906_842_624];
        yield ['1024', 1024];
    }
}
