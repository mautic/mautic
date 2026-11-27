<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Diagnostics;

use Mautic\EmailBundle\Diagnostics\ScannerHoneypotLink;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScannerHoneypotLinkTest extends TestCase
{
    public function testTheLinkIsHiddenFromPeopleAndFromAssistiveTechnology(): void
    {
        $html = ScannerHoneypotLink::getHtml();

        $this->assertStringContainsString('href="'.ScannerHoneypotLink::URL_TOKEN.'"', $html);
        $this->assertStringContainsString('display:none', $html);
        $this->assertStringContainsString('mso-hide:all', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertSame('', trim(strip_tags($html)), 'there is no visible text in it');
    }

    #[DataProvider('contentProvider')]
    public function testRemoveFrom(string $content, string $expected): void
    {
        $this->assertSame($expected, ScannerHoneypotLink::removeFrom($content));
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function contentProvider(): \Generator
    {
        yield 'the link of an email' => [
            '<p>Hi</p>'.ScannerHoneypotLink::getHtml().'</body>',
            '<p>Hi</p></body>',
        ];

        yield 'content without the link is untouched' => [
            '<p>Hi <a href="https://example.com">there</a></p>',
            '<p>Hi <a href="https://example.com">there</a></p>',
        ];

        yield 'an empty string' => ['', ''];

        yield 'the link with single quotes and other attributes' => [
            "<p>Hi</p><a class='x' href='{scanner_honeypot_url}' style='display:none'> </a><p>Bye</p>",
            '<p>Hi</p><p>Bye</p>',
        ];

        yield 'the link in capitals' => [
            '<P>Hi</P><A HREF="{scanner_honeypot_url}"></A>',
            '<P>Hi</P>',
        ];

        yield 'a link to a real page next to it stays' => [
            '<a href="https://example.com">x</a>'.ScannerHoneypotLink::getHtml().'<a href="https://example.org">y</a>',
            '<a href="https://example.com">x</a><a href="https://example.org">y</a>',
        ];

        yield 'a link that holds visible text is not the honeypot' => [
            '<a href="{scanner_honeypot_url}">Click</a>',
            '<a href="{scanner_honeypot_url}">Click</a>',
        ];

        yield 'a link to another token stays' => [
            '<a href="{unsubscribe_url}"></a>',
            '<a href="{unsubscribe_url}"></a>',
        ];

        yield 'a token in the text stays' => [
            '<p>{scanner_honeypot_url}</p>',
            '<p>{scanner_honeypot_url}</p>',
        ];
    }

    public function testThePlainTextVersionHasNoHoneypotLink(): void
    {
        $plainText = new PlainTextHelper();
        $plainText->setHtml('<p>Hello <a href="https://example.com">there</a></p>'.ScannerHoneypotLink::getHtml());

        $this->assertSame('Hello there [https://example.com]', $plainText->getText());
    }
}
