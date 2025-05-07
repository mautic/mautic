<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatterHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    private DateHelper $dateHelper;

    private FormatterHelper $formatterHelper;

    /**
     * @var CoreParametersHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $coreParametersHelper;

    private string $previousTimeZone;

    protected function setUp(): void
    {
        $this->previousTimeZone     = date_default_timezone_get();
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->dateHelper           = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->translator,
            $this->coreParametersHelper
        );
        $this->formatterHelper               = new FormatterHelper($this->dateHelper, $this->translator);
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previousTimeZone);
    }

    public function testStrictHtmlFormatIsRemovingScriptTags(): void
    {
        $sample = '<a href="/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.<script>console.log(\'script is running\');</script><SCRIPT>console.log(\'CAPITAL script is running\');</SCRIPT>';

        $expected = '<a href="/s/webhooks/view/31" data-toggle="ajax">test</a> has been stopped because the response HTTP code was 410, which means the reciever doesn\'t want us to send more requests.console.log(\'script is running\');console.log(\'CAPITAL script is running\');';

        $result = $this->formatterHelper->_($sample, 'html');

        $this->assertEquals($expected, $result);
    }

    public function testBooleanFormat(): void
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['mautic.core.yes'], ['mautic.core.no'])
            ->willReturnOnConsecutiveCalls('yes', 'no');

        $result = $this->formatterHelper->_(1, 'bool');
        $this->assertEquals('yes', $result);

        $result = $this->formatterHelper->_(0, 'bool');
        $this->assertEquals('no', $result);
    }

    public function testFloatFormat(): void
    {
        $result = $this->formatterHelper->_(1.55, 'float');

        $this->assertEquals('1.5500', $result);
        $this->assertEquals('string', gettype($result));
    }

    public function testIntFormat(): void
    {
        $result = $this->formatterHelper->_(10, 'int');

        $this->assertSame('10', $result);
        $this->assertEquals('string', gettype($result));
    }

    /**
     * @dataProvider stringProvider
     *
     * @param mixed $input
     * @param mixed $expected
     */
    public function testNormalizeStringValue($input, $expected): void
    {
        date_default_timezone_set('Europe/Paris');
        $this->assertEquals($this->formatterHelper->normalizeStringValue($input), $expected);
    }

    /**
     * @return iterable<array<mixed>>
     */
    public static function stringProvider(): iterable
    {
        // string
        yield ['random string', 'random string'];

        // integer
        yield [1, 1];

        // bool
        yield [false, false];

        // date
        yield ['2020-02-02', '2020-02-02'];

        // date time
        yield ['2021-02-21 18:00:00', 'February 21, 2021 6:00 pm'];

        // date object
        yield [
            \DateTime::createFromFormat('Y-m-d H:i:s', 'now', new \DateTimeZone('UTC')),
            \DateTime::createFromFormat('Y-m-d H:i:s', 'now', new \DateTimeZone('UTC')),
        ];
    }

    /**
     * @dataProvider urlFormatProvider
     */
    public function testUrlFormat(string $url, string $expected): void
    {
        $result = $this->formatterHelper->_($url, 'url');
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array<string>>
     */
    public function urlFormatProvider(): array
    {
        return [
            'normal url' => [
                'http://example.com',
                '<a href="http://example.com" target="_blank">http://example.com</a>',
            ],
            'malicious url' => [
                'http://example.com"><script>alert("XSS")</script>',
                '<a href="http://example.com&#34;&#62;&#60;script&#62;alert(&#34;XSS&#34;)&#60;/script&#62;" target="_blank">http://example.com&#34;&#62;&#60;script&#62;alert(&#34;XSS&#34;)&#60;/script&#62;</a>',
            ],
            'malicious url2' => [
                'http://example.com?a="<b>test</b>',
                '<a href="http://example.com?a=%22%3Cb%3Etest%3C%2Fb%3E" target="_blank">http://example.com?a=%22%3Cb%3Etest%3C%2Fb%3E</a>',
            ],
            'url with single GET parameter' => [
                'http://example.com/page?param=value',
                '<a href="http://example.com/page?param=value" target="_blank">http://example.com/page?param=value</a>',
            ],
            'url with multiple GET parameters' => [
                'http://example.com/search?q=test&page=1&sort=desc',
                '<a href="http://example.com/search?q=test&page=1&sort=desc" target="_blank">http://example.com/search?q=test&page=1&sort=desc</a>',
            ],
            'url with encoded GET parameters' => [
                'http://example.com/search?q=hello+world&lang=en',
                '<a href="http://example.com/search?q=hello+world&lang=en" target="_blank">http://example.com/search?q=hello+world&lang=en</a>',
            ],
            'url with special characters in GET parameters' => [
                'http://example.com/path?param=value&special=!@#$%^&*()',
                '<a href="http://example.com/path?param=value&special=%21%40#$%^&*()" target="_blank">http://example.com/path?param=value&special=%21%40#$%^&*()</a>',
            ],
            'https url' => [
                'https://secure.example.com',
                '<a href="https://secure.example.com" target="_blank">https://secure.example.com</a>',
            ],
            'url with port number' => [
                'http://example.com:8080/path',
                '<a href="http://example.com:8080/path" target="_blank">http://example.com:8080/path</a>',
            ],
            'url with username and password' => [
                'http://user:pass@example.com',
                '<a href="http://user:pass@example.com" target="_blank">http://user:pass@example.com</a>',
            ],
            'url with fragment identifier' => [
                'http://example.com/page#section',
                '<a href="http://example.com/page#section" target="_blank">http://example.com/page#section</a>',
            ],
        ];
    }

    /**
     * @dataProvider emailFormatProvider
     */
    public function testEmailFormat(string $email, string $expected): void
    {
        $result = $this->formatterHelper->_($email, 'email');
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array<string>>
     */
    public function emailFormatProvider(): array
    {
        return [
            'normal email' => [
                'user@example.com',
                '<a href="mailto:user@example.com">user@example.com</a>',
            ],
            'email with alias' => [
                'user.one+test@example.com',
                '<a href="mailto:user.one+test@example.com">user.one+test@example.com</a>',
            ],
            'malicious email' => [
                'user@example.com"><script>alert("XSS")</script>',
                '<a href="mailto:user@example.com&#34;&#62;&#60;script&#62;alert(&#34;XSS&#34;)&#60;/script&#62;">user@example.com&#34;&#62;&#60;script&#62;alert(&#34;XSS&#34;)&#60;/script&#62;</a>',
            ],
        ];
    }

    public function testArrayFormat(): void
    {
        $input    = ['<script>alert("XSS")</script>'];
        $result   = $this->formatterHelper->_($input, 'array');
        $expected = '&#60;script&#62;alert(&#34;XSS&#34;)&#60;/script&#62;';
        $this->assertEquals($expected, $result);
    }
}
