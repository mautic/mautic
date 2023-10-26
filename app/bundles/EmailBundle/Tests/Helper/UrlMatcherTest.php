<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\UrlMatcher;

class UrlMatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testUrlIsFound()
    {
        $urls = [
            'google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'google.com'));
    }

    public function testUrlWithSlashIsMatched()
    {
        $urls = [
            'https://google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com'));
    }

    public function testUrlWithEscapedSlashesIsMatched()
    {
        $urls = [
            'https:\/\/google.com\/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
    }

    public function testUrlWithEndingSlash()
    {
        $urls = [
            'https://google.com/hello/',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello/'));
    }

    public function testUrlWithoutHttpPrefix()
    {
        $urls = [
            'google.com/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'http://google.com/hello/'));
    }

    public function testUrlWithoutHttp()
    {
        $urls = [
            '//google.com/hello',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://google.com/hello'));
        $this->assertTrue(UrlMatcher::hasMatch($urls, '//google.com/hello'));
    }

    public function testUrlMismatch()
    {
        $urls = [
            'http://google.com',
        ];

        $this->assertFalse(UrlMatcher::hasMatch($urls, 'https://yahoo.com'));
    }

    public function testFTPSchemeMisMatch()
    {
        $urls = [
            'ftp://google.com',
        ];

        $this->assertFalse(UrlMatcher::hasMatch($urls, 'https://google.com'));
    }

    public function testFTPSchemeMatch()
    {
        $urls = [
            'ftp://google.com',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urls, 'ftp://google.com'));
    }
}
