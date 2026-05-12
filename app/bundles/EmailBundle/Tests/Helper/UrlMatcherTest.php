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

    public function testUrlWithQueryParametersEncoded(): void
    {
        $urls = [
            'https://someurl.com/advanced-search?body_standard[0]=Car&body_standard[1]=Sedan',
        ];

        // Test matching with original URL (not encoded)
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://someurl.com/advanced-search?body_standard[0]=Car&body_standard[1]=Sedan'));

        // Test matching with URL encoded version
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://someurl.com/advanced-search?body_standard%5B0%5D=Car&body_standard%5B1%5D=Sedan'));
    }

    public function testUrlWithQueryParametersDecoded(): void
    {
        $urls = [
            'https://someurl.com/advanced-search?body_standard%5B0%5D=Car&body_standard%5B1%5D=Sedan',
        ];

        // Test matching with URL encoded version (original)
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://someurl.com/advanced-search?body_standard%5B0%5D=Car&body_standard%5B1%5D=Sedan'));

        // Test matching with decoded version
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'https://someurl.com/advanced-search?body_standard[0]=Car&body_standard[1]=Sedan'));
    }

    public function testUrlWithArrayParameterNotation(): void
    {
        // Test the specific case where empty array notation [] should match indexed notation [0], [1]
        $urls = [
            'someurl.com/advanced-search?body_standard[]=Car&body_standard[]=Sedan',
        ];

        // Should match the indexed array notation
        $this->assertTrue(UrlMatcher::hasMatch($urls, 'someurl.com/advanced-search?body_standard[0]=Car&body_standard[1]=Sedan'));

        // Should also work in reverse - indexed notation in list should match empty array notation in search
        $urlsIndexed = [
            'someurl.com/advanced-search?body_standard[0]=Car&body_standard[1]=Sedan',
        ];

        $this->assertTrue(UrlMatcher::hasMatch($urlsIndexed, 'someurl.com/advanced-search?body_standard[]=Car&body_standard[]=Sedan'));
    }
}
