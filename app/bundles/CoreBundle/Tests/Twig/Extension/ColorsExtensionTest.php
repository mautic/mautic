<?php

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\ColorsExtension;
use PHPUnit\Framework\TestCase;

class ColorsExtensionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('colorProvider')]
    public function testGetContrastColor(string $input, string $expected): void
    {
        $ext = new ColorsExtension();
        $this->assertSame($expected, $ext->getContrastColor($input));
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function colorProvider(): array
    {
        return [
            // Light backgrounds should return black
            ['#FFFFFF', 'black'],
            ['FFFFFF', 'black'],
            ['#FED039', 'black'],
            ['FED039', 'black'],
            ['#FFF', 'black'],
            ['FFF', 'black'],
            // Dark backgrounds should return white
            ['#000000', 'white'],
            ['000000', 'white'],
            ['#123456', 'white'],
            ['123456', 'white'],
            ['#812407', 'white'],
            ['812407', 'white'],
            // Invalid input returns black
            ['notacolor', 'black'],
            ['', 'black'],
            ['#GGGGGG', 'black'],
        ];
    }
}
