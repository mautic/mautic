<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AbstractLocalDataLookupTest extends TestCase
{
    #[DataProvider('provideUrlToClean')]
    public function testCleanUrl(string $url, string $expected): void
    {
        Assert::assertSame($expected, AbstractLocalDataLookup::cleanUrl($url));
    }

    public static function provideUrlToClean(): \Generator
    {
        yield 'URL with user:pass with path' => ['https://user:pass@domain.tld/path?query=1#fragment=anchor', 'https://domain.tld/path?query=1#fragment=anchor'];
        yield 'URL with user:pass with empty path' => ['https://user:pass@domain.tld/?query=1#fragment=anchor', 'https://domain.tld/?query=1#fragment=anchor'];
        yield 'URL with user:pass without path' => ['https://user:pass@domain.tld?query=1#fragment=anchor', 'https://domain.tld?query=1#fragment=anchor'];

        foreach (['user', 'username'] as $userParameter) {
            foreach (['pwd', 'pass', 'password'] as $passwordParameter) {
                yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') with path' => ['https://domain.tld/path?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd#fragment=anchor', 'https://domain.tld/path?query=1#fragment=anchor'];
                yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') with empty path' => ['https://domain.tld/?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd#fragment=anchor', 'https://domain.tld/?query=1#fragment=anchor'];
                yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') without path' => ['https://domain.tld?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd#fragment=anchor', 'https://domain.tld?query=1#fragment=anchor'];
            }
        }
    }
}
