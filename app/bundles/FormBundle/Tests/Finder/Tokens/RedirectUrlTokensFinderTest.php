<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Finder\Tokens;

use Mautic\FormBundle\Finder\Tokens\RedirectUrlTokensFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RedirectUrlTokensFinder::class)]
class RedirectUrlTokensFinderTest extends TestCase
{
    private const NOT_VALID_URLS = [
        'ttps://example.com',
        'https://example',
        'https:/example.com',
        'example.com?test1=123&test2=abc',
    ];

    private const HOMEPAGE_URLS = [
        'https://example.com/',
        'https://example.com',
    ];

    private const PAGE_URLS = [
        'https://example.com/page1/',
        'https://example.com/page2/lorem-ipsum/',
        'https://example.com/page2/lorem-ipsum?test1&test2',
        'https://example.com/page2/lorem-ipsum?test1=123&test2=abc',
    ];

    private const TOKENIZED_URLS = [
        '{pagelink=123}/page2/lorem-ipsum?{formfield=abc}&{contactfield=abc123}',
        '{pagelink=123}/page2/lorem-ipsum?test1&test2&{formfield=abc}&{contactfield=abc123}',
        '{pagelink=123}/page2/lorem-ipsum?test1=123&test2=abc&{formfield=abc}&{contactfield=abc123}',
    ];

    private RedirectUrlTokensFinder $redirectUrlTokensFinder;

    public static function provideUrlToCheck(): \Generator
    {
        yield 'empty string' => [
            '',
            false,
        ];

        // ---

        yield 'not valid url 1' => [
            'example',
            false,
        ];

        yield 'not valid url 2' => [
            self::NOT_VALID_URLS[0],
            false,
        ];

        yield 'not valid url 3' => [
            self::NOT_VALID_URLS[1],
            false,
        ];

        yield 'not valid url 4' => [
            self::NOT_VALID_URLS[2],
            false,
        ];

        yield 'not valid url 5' => [
            self::NOT_VALID_URLS[3],
            false,
        ];

        // ---

        yield 'homepage with ending slash' => [
            self::HOMEPAGE_URLS[0],
            false,
        ];

        yield 'homepage without ending slash' => [
            self::HOMEPAGE_URLS[1],
            false,
        ];

        yield 'page url 1' => [
            self::PAGE_URLS[0],
            false,
        ];

        yield 'page url 2' => [
            self::PAGE_URLS[1],
            false,
        ];

        yield 'page url with query parameters 1' => [
            self::PAGE_URLS[2],
            false,
        ];

        yield 'page url with query parameters 2' => [
            self::PAGE_URLS[3],
            false,
        ];

        // ---

        yield 'not valid url 1 - with tokens' => [
            'example?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'not valid url 2 - with tokens' => [
            'ttps://example.com?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'not valid url 3 - with tokens' => [
            'https://example?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'not valid url 4 - with tokens' => [
            'https:/example.com?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'not valid url 5 - with tokens' => [
            'example.com?test1=123&test2=abc&{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'missing curly braces in some tokens' => [
            'https:/example.com?{pagelink=123}&formfield=abc}&{contactfield=abc123',
            true,
        ];

        yield 'with unknown tokens' => [
            'https:/example.com?{pagelink=123}&{formfield=abc}&{contactfield=abc123}&{foo=bar}&{lorem=ipsum}',
            true,
        ];

        // ---

        yield 'tokens only' => [
            '{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'tokens mixed with static url' => [
            self::TOKENIZED_URLS[0],
            true,
        ];

        yield 'tokens mixed with static url and query parameters 1' => [
            self::TOKENIZED_URLS[1],
            true,
        ];

        yield 'tokens mixed with static url and query parameters 2' => [
            self::TOKENIZED_URLS[2],
            true,
        ];

        // ---

        yield 'homepage with ending slash - with tokens' => [
            'https://example.com/?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'homepage without ending slash - with tokens' => [
            'https://example.com?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'page url 1 - with tokens' => [
            'https://example.com/page1/?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'page url 2 - with tokens' => [
            'https://example.com/page2/lorem-ipsum/?{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'page url with query parameters 1 - with tokens' => [
            'https://example.com/page2/lorem-ipsum?test1&test2&{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'page url with query parameters 2 - with tokens' => [
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&{pagelink=123}&{formfield=abc}&{contactfield=abc123}',
            true,
        ];

        yield 'page url with query parameters - multiple same tokens' => [
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&{pagelink=123}&{formfield=abc}&{formfield=def}&{contactfield=abc123}&{contactfield=def456}',
            true,
        ];

        yield 'page url without query parameters' => [
            'https://example.com/page2/{pagelink=123}/{formfield=abc}/lorem-ipsum/{contactfield=abc123}',
            true,
        ];
    }

    public static function provideUrlToReplace(): \Generator
    {
        yield 'empty string' => [
            '',
            '',
        ];

        // ---

        yield 'not valid url 1' => [
            'example',
            'example',
        ];

        yield 'not valid url 2' => [
            self::NOT_VALID_URLS[0],
            self::NOT_VALID_URLS[0],
        ];

        yield 'not valid url 3' => [
            self::NOT_VALID_URLS[1],
            self::NOT_VALID_URLS[1],
        ];

        yield 'not valid url 4' => [
            self::NOT_VALID_URLS[2],
            self::NOT_VALID_URLS[2],
        ];

        yield 'not valid url 5' => [
            self::NOT_VALID_URLS[3],
            self::NOT_VALID_URLS[3],
        ];

        // ---

        yield 'homepage with ending slash' => [
            self::HOMEPAGE_URLS[0],
            self::HOMEPAGE_URLS[0],
        ];

        yield 'homepage without ending slash' => [
            self::HOMEPAGE_URLS[1],
            self::HOMEPAGE_URLS[1],
        ];

        yield 'page url 1' => [
            self::PAGE_URLS[0],
            self::PAGE_URLS[0],
        ];

        yield 'page url 2' => [
            self::PAGE_URLS[1],
            self::PAGE_URLS[1],
        ];

        yield 'page url with query parameters 1' => [
            self::PAGE_URLS[2],
            self::PAGE_URLS[2],
        ];

        yield 'page url with query parameters 2' => [
            self::PAGE_URLS[3],
            self::PAGE_URLS[3],
        ];

        // ---

        yield 'not valid url 1 - with tokens' => [
            'example?{formfield=abc}&{contactfield=abc123}',
            'example?formfield-1&contactfield-2',
        ];

        yield 'not valid url 2 - with tokens' => [
            'ttps://example.com?{formfield=abc}&{contactfield=abc123}',
            'ttps://example.com?formfield-1&contactfield-2',
        ];

        yield 'not valid url 3 - with tokens' => [
            'https://example?{formfield=abc}&{contactfield=abc123}',
            'https://example?formfield-1&contactfield-2',
        ];

        yield 'not valid url 4 - with tokens' => [
            'https:/example.com?{formfield=abc}&{contactfield=abc123}',
            'https:/example.com?formfield-1&contactfield-2',
        ];

        yield 'not valid url 5 - with tokens' => [
            'example.com?test1=123&test2=abc&{formfield=abc}&{contactfield=abc123}',
            'example.com?test1=123&test2=abc&formfield-1&contactfield-2',
        ];

        yield 'missing curly braces in some tokens' => [
            '{pagelink=123}?formfield=abc}&{contactfield=abc123',
            'https://example.com?formfield=abc}&{contactfield=abc123',
        ];

        yield 'with unknown tokens' => [
            '{pagelink=123}?{formfield=abc}&{contactfield=abc123}&{foo=bar}&{lorem=ipsum}',
            'https://example.com?formfield-2&contactfield-3&{foo=bar}&{lorem=ipsum}',
        ];

        // ---

        yield 'tokens only' => [
            '{pagelink=123}?{formfield=abc}&{contactfield=abc123}',
            'https://example.com?formfield-2&contactfield-3',
        ];

        yield 'tokens mixed with static url' => [
            self::TOKENIZED_URLS[0],
            'https://example.com/page2/lorem-ipsum?formfield-2&contactfield-3',
        ];

        yield 'tokens mixed with static url and query parameters 1' => [
            self::TOKENIZED_URLS[1],
            'https://example.com/page2/lorem-ipsum?test1&test2&formfield-2&contactfield-3',
        ];

        yield 'tokens mixed with static url and query parameters 2' => [
            self::TOKENIZED_URLS[2],
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&formfield-2&contactfield-3',
        ];

        yield 'page url with query parameters - multiple same tokens' => [
            '{pagelink=123}/page2/lorem-ipsum?test1=123&test2=abc&{formfield=abc}&{formfield=def}&{contactfield=abc123}&{contactfield=def456}',
            'https://example.com/page2/lorem-ipsum?test1=123&test2=abc&formfield-2&formfield-3&contactfield-4&contactfield-5',
        ];

        yield 'page url without query parameters' => [
            '{pagelink=123}/page2/{formfield=abc}/lorem-ipsum/{contactfield=abc123}',
            'https://example.com/page2/formfield-2/lorem-ipsum/contactfield-3',
        ];
    }

    #[DataProvider('provideUrlToCheck')]
    public function testHasTokens(string $url, bool $expected): void
    {
        self::assertSame($expected, $this->redirectUrlTokensFinder->hasTokens($url));
    }

    #[DataProvider('provideUrlToReplace')]
    public function testReplaceTokensWithDummyData(string $url, string $expected): void
    {
        self::assertSame($expected, $this->redirectUrlTokensFinder->replaceTokensWithDummyData($url));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->redirectUrlTokensFinder = new RedirectUrlTokensFinder();
    }
}
