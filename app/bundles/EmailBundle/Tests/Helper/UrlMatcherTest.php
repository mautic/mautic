<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\UrlMatcher;

class UrlMatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testUrlIsFound(): void
    {
        $urls = [
            'google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'google.com'));
    }

    public function testUrlWithSlashIsMatched(): void
    {
        $urls = [
            'https://google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com'));
    }

    public function testUrlWithEscapedSlashesIsMatched(): void
    {
        $urls = [
            'https:\/\/google.com\/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
    }

    public function testUrlWithEndingSlash(): void
    {
        $urls = [
            'https://google.com/hello/',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello/'));
    }

    public function testUrlWithoutHttpPrefix(): void
    {
        $urls = [
            'google.com/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'http://google.com/hello/'));
    }

    public function testUrlWithoutHttp(): void
    {
        $urls = [
            '//google.com/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, '//google.com/hello'));
    }

    public function testUrlMismatch(): void
    {
        $urls = [
            'http://google.com',
        ];

        $this->assertFalse(UrlMatcher::hasMatch($urls, 'https://yahoo.com'));
    }

    public function testFTPSchemeMisMatch(): void
    {
        $urls = [
            'ftp://google.com',
        ];

        $this->assertFalse(UrlMatcher::hasMatch($urls, 'https://google.com'));
    }

    public function testFTPSchemeMatch(): void
    {
        $urls = [
            'ftp://google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'ftp://google.com'));
    }
}
