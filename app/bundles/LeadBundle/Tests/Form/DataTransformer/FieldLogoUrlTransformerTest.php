<?php

namespace Mautic\LeadBundle\Tests\Form\DataTransformer;

use Mautic\LeadBundle\Form\DataTransformer\FieldLogoUrlTransformer;

class FieldLogoUrlTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideTransformCases
     */
    public function testTransform(string $input, string $expected): void
    {
        $t = new FieldLogoUrlTransformer();
        $this->assertSame($expected, $t->transform($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function provideTransformCases(): array
    {
        return [
            'adds http when missing'              => ['example.com', 'https://example.com'],
            'trims whitespace and adds http'      => ['  example.com  ', 'https://example.com'],
            'keeps http'                          => ['http://example.com', 'http://example.com'],
            'keeps https'                         => ['https://example.com', 'https://example.com'],
            'removes query (path present)'        => ['https://example.com/path?utm=1&id=2', 'https://example.com/path?utm=1&id=2'],
            'removes query (no path)'             => ['example.com?x=1', 'https://example.com?x=1'],
        ];
    }

    /**
     * @dataProvider provideTransformCases
     */
    public function testReverseTransform(string $input, string $expected): void
    {
        $t = new FieldLogoUrlTransformer();
        $this->assertSame($expected, $t->reverseTransform($input));
    }
}
