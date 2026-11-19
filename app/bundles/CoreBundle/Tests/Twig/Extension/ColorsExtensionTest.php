<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\ColorsExtension;
use PHPUnit\Framework\TestCase;

final class ColorsExtensionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('colorProvider')]
    public function testGetContrastColor(string $input, string $expected): void
    {
        $ext = new ColorsExtension();
        $this->assertSame($expected, $ext->getContrastColor($input));
    }

    /**
     * @return \Iterator<int, array{string, string}>
     */
    public static function colorProvider(): \Iterator
    {
        // Light backgrounds should return black
        yield ['#FFFFFF', 'black'];
        yield ['FFFFFF', 'black'];
        yield ['#FED039', 'black'];
        yield ['FED039', 'black'];
        yield ['#FFF', 'black'];
        yield ['FFF', 'black'];
        // Dark backgrounds should return white
        yield ['#000000', 'white'];
        yield ['000000', 'white'];
        yield ['#123456', 'white'];
        yield ['123456', 'white'];
        yield ['#812407', 'white'];
        yield ['812407', 'white'];
        // Invalid input returns black
        yield ['notacolor', 'black'];
        yield ['', 'black'];
        yield ['#GGGGGG', 'black'];
    }
}
