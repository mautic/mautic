<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Extension;

use Mautic\CoreBundle\Twig\Extension\PurifyExtension;
use PHPUnit\Framework\TestCase;

class PurifyExtensionTest extends TestCase
{
    private PurifyExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new PurifyExtension();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('purifyHtmlDataProvider')]
    public function testPurifyAllowTargetBlank(?string $input, string $expected): void
    {
        $result = $this->extension->purifyAllowTargetBlank($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{input: ?string, expected: string}>
     */
    public static function purifyHtmlDataProvider(): array
    {
        return [
            'null input' => [
                'input'    => null,
                'expected' => '',
            ],
            'empty string' => [
                'input'    => '',
                'expected' => '',
            ],
            'plain text' => [
                'input'    => 'Hello World',
                'expected' => 'Hello World',
            ],
            'basic html' => [
                'input'    => '<p>Hello World</p>',
                'expected' => '<p>Hello World</p>',
            ],
            'link with target blank' => [
                'input'    => '<a href="https://example.com" target="_blank">Link</a>',
                'expected' => '<a href="https://example.com" target="_blank" rel="noreferrer noopener">Link</a>',
            ],
            'malicious html' => [
                'input'    => '<script>alert("xss")</script><p>Hello</p>',
                'expected' => '<p>Hello</p>',
            ],
            'mixed content' => [
                'input'    => '<p>Hello</p><script>alert("xss")</script><a href="https://example.com" target="_blank">Link</a>',
                'expected' => '<p>Hello</p><a href="https://example.com" target="_blank" rel="noreferrer noopener">Link</a>',
            ],
            'invalid html' => [
                'input'    => '<p>Unclosed paragraph<a>Unclosed link',
                'expected' => '<p>Unclosed paragraph<a>Unclosed link</a></p>',
            ],
        ];
    }
}
