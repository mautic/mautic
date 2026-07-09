<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\AbstractLocalDataLookup;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AbstractLocalDataLookupTest extends TestCase
{
    #[DataProvider('provideUrlToClean')]
    public function testCleanUrl(string $url, string $expected): void
    {
        Assert::assertSame($expected, AbstractLocalDataLookup::cleanUrl($url));
    }

    public static function provideUrlToClean(): \Generator
    {
        foreach ([' with port' => ':768', ' without port' => ''] as $portLabel => $port) {
            foreach ([' without anchor' => '', ' with empty anchor' => '#', ' with non-empty anchor' => '#fragment=anchor'] as $anchorLabel => $fragment) {
                yield 'URL with user:pass with path'.$portLabel.$anchorLabel => ['https://user:pass@domain.tld'.$port.'/path?query=1'.$fragment, 'https://domain.tld'.$port.'/path?query=1'.$fragment];
                yield 'URL with user:pass with empty path'.$portLabel.$anchorLabel => ['https://user:pass@domain.tld'.$port.'/?query=1'.$fragment, 'https://domain.tld'.$port.'/?query=1'.$fragment];
                yield 'URL with user:pass without path'.$portLabel.$anchorLabel => ['https://user:pass@domain.tld'.$port.'?query=1'.$fragment, 'https://domain.tld'.$port.'?query=1'.$fragment];

                foreach (['user', 'username'] as $userParameter) {
                    foreach (['pwd', 'pass', 'password'] as $passwordParameter) {
                        yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') with path'.$portLabel.$anchorLabel => ['https://domain.tld'.$port.'/path?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd'.$fragment, 'https://domain.tld'.$port.'/path?query=1'.$fragment];
                        yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') with empty path'.$portLabel.$anchorLabel => ['https://domain.tld'.$port.'/?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd'.$fragment, 'https://domain.tld'.$port.'/?query=1'.$fragment];
                        yield 'URL with credentials in URL ('.$userParameter.', '.$passwordParameter.') without path'.$portLabel.$anchorLabel => ['https://domain.tld'.$port.'?query=1&'.$userParameter.'=user&'.$passwordParameter.'=pwd'.$fragment, 'https://domain.tld'.$port.'?query=1'.$fragment];
                    }
                }
            }
        }

        yield 'Malformed URL' => ['malformed string', 'malformed string'];
        yield 'Malformed URL #2' => ['https://?query', 'https://?query'];
        yield 'No scheme' => ['the.host/path?query', 'the.host/path?query'];
    }
}
