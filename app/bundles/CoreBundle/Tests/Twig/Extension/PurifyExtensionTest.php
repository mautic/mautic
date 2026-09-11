<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\PurifyExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PurifyExtensionTest extends TestCase
{
    private PurifyExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new PurifyExtension();
    }

    #[DataProvider('purifyHtmlDataProvider')]
    public function testPurifyAllowTargetBlank(?string $input, string $expected): void
    {
        $result = $this->extension->purifyAllowTargetBlank($input);
        $this->assertSame($expected, $result);
    }

    /**
     * @return \Iterator<string, array{input: (string|null), expected: string}>
     */
    public static function purifyHtmlDataProvider(): \Iterator
    {
        yield 'null input' => [
            'input'    => null,
            'expected' => '',
        ];
        yield 'empty string' => [
            'input'    => '',
            'expected' => '',
        ];
        yield 'plain text' => [
            'input'    => 'Hello World',
            'expected' => 'Hello World',
        ];
        yield 'basic html' => [
            'input'    => '<p>Hello World</p>',
            'expected' => '<p>Hello World</p>',
        ];
        yield 'link with target blank' => [
            'input'    => '<a href="https://example.com" target="_blank">Link</a>',
            'expected' => '<a href="https://example.com" target="_blank" rel="noreferrer noopener">Link</a>',
        ];
        yield 'malicious html' => [
            'input'    => '<script>alert("xss")</script><p>Hello</p>',
            'expected' => '<p>Hello</p>',
        ];
        yield 'mixed content' => [
            'input'    => '<p>Hello</p><script>alert("xss")</script><a href="https://example.com" target="_blank">Link</a>',
            'expected' => '<p>Hello</p><a href="https://example.com" target="_blank" rel="noreferrer noopener">Link</a>',
        ];
        yield 'invalid html' => [
            'input'    => '<p>Unclosed paragraph<a>Unclosed link',
            'expected' => '<p>Unclosed paragraph<a>Unclosed link</a></p>',
        ];
    }
}
