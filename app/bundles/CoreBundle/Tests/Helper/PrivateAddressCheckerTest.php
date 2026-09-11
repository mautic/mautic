<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\PrivateAddressChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrivateAddressCheckerTest extends TestCase
{
    private PrivateAddressChecker $checker;

    private PrivateAddressChecker $checkerWithMockedDns;

    protected function setUp(): void
    {
        // Regular checker for IP tests
        $this->checker = new PrivateAddressChecker();

        // Checker with mocked DNS resolver for URL tests
        $this->checkerWithMockedDns = new PrivateAddressChecker(
            fn (string $host): array|false => match ($host) {
                'private.example.com' => ['192.168.1.1'],
                'public.example.com'  => ['203.0.113.1'],
                'api.example.com'     => ['8.8.8.8'],
                'localhost'           => ['127.0.0.1'],
                default               => false,
            }
        );
    }

    #[DataProvider('privateIpProvider')]
    public function testIsPrivateIpReturnsTrue(string $ip): void
    {
        $this->assertTrue($this->checker->isPrivateIp($ip));
    }

    #[DataProvider('publicIpProvider')]
    public function testIsPrivateIpReturnsFalse(string $ip): void
    {
        $this->assertFalse($this->checker->isPrivateIp($ip));
    }

    #[DataProvider('privateUrlProvider')]
    public function testIsPrivateUrlReturnsTrue(string $url): void
    {
        $this->assertTrue($this->checkerWithMockedDns->isPrivateUrl($url));
    }

    #[DataProvider('publicUrlProvider')]
    public function testIsPrivateUrlReturnsFalse(string $url): void
    {
        $this->assertFalse($this->checkerWithMockedDns->isPrivateUrl($url));
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function privateIpProvider(): \Iterator
    {
        yield 'IPv4 Local' => ['127.0.0.1'];
        yield 'IPv4 Private Class A' => ['10.0.0.1'];
        yield 'IPv4 Private Class B' => ['172.16.0.1'];
        yield 'IPv4 Private Class C' => ['192.168.0.1'];
        yield 'IPv4 Link Local' => ['169.254.0.1'];
        yield 'IPv6 Localhost' => ['::1'];
        yield 'IPv6 Unique Local' => ['fc00::1'];
        yield 'IPv6 Link Local' => ['fe80::1'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function publicIpProvider(): \Iterator
    {
        yield 'IPv4 Public' => ['8.8.8.8'];
        yield 'IPv4 Public Alternative' => ['203.0.113.1'];
        yield 'IPv6 Public' => ['2001:4860:4860::8888'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function privateUrlProvider(): \Iterator
    {
        yield 'Localhost' => ['http://localhost'];
        yield 'Localhost with port' => ['http://localhost:8080'];
        yield 'IPv4 Private' => ['http://192.168.1.1'];
        yield 'IPv4 Private with path' => ['https://10.0.0.1/path'];
        yield 'IPv6 Localhost' => ['http://[::1]'];
        yield 'IPv6 Private' => ['http://[fc00::1]'];
        yield 'Domain resolving to private IP' => ['http://private.example.com'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function publicUrlProvider(): \Iterator
    {
        yield 'Public Domain' => ['http://public.example.com'];
        yield 'IPv4 Public' => ['http://8.8.8.8'];
        yield 'IPv6 Public' => ['http://[2001:4860:4860::8888]'];
        yield 'HTTPS URL' => ['https://api.example.com/v1'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function invalidUrlProvider(): \Iterator
    {
        yield 'Empty URL' => [''];
        yield 'Invalid Format' => ['not-a-url'];
        yield 'Missing Protocol' => ['example.com'];
        yield 'Invalid Characters' => ['http://example.com\\invalid'];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testIsPrivateUrlThrowsExceptionForInvalidUrls(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->checkerWithMockedDns->isPrivateUrl($url);
    }

    public function testUnresolvableHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve hostname unresolvable.example.com');
        $this->checkerWithMockedDns->isPrivateUrl('http://unresolvable.example.com');
    }

    public function testIsPrivateIpWithInvalidIPFormat(): void
    {
        $this->assertFalse($this->checker->isPrivateIp('invalid-ip'));
    }

    #[DataProvider('edgeCaseUrlProvider')]
    public function testEdgeCaseUrls(string $url, bool $expectedResult): void
    {
        $this->assertSame($expectedResult, $this->checkerWithMockedDns->isPrivateUrl($url));
    }

    /**
     * @return \Iterator<string, array{string, bool}>
     */
    public static function edgeCaseUrlProvider(): \Iterator
    {
        yield 'URL with Port' => ['http://[::1]:8080', true];
        yield 'IPv6 with Zone Index' => ['http://[fe80::1%eth0]', true];
        yield 'IPv6 Full Format' => ['http://[2001:0db8:85a3:0000:0000:8a2e:0370:7334]', false];
    }

    #[DataProvider('allowedUrlProvider')]
    public function testIsAllowedUrlReturnsTrue(string $url): void
    {
        $this->checkerWithMockedDns->setAllowedPrivateAddresses(['192.168.1.1', 'localhost', '::1']);
        $this->assertTrue($this->checkerWithMockedDns->isAllowedUrl($url));
    }

    #[DataProvider('disallowedUrlProvider')]
    public function testIsAllowedUrlReturnsFalse(string $url): void
    {
        $this->checkerWithMockedDns->setAllowedPrivateAddresses(['192.168.1.2', '10.0.0.1']);
        $this->assertFalse($this->checkerWithMockedDns->isAllowedUrl($url));
    }

    public function testIsAllowedUrlWithEmptyAllowedAddresses(): void
    {
        $this->checkerWithMockedDns->setAllowedPrivateAddresses([]);
        $this->assertTrue($this->checkerWithMockedDns->isAllowedUrl('http://public.example.com'));
        $this->assertFalse($this->checkerWithMockedDns->isAllowedUrl('http://private.example.com'));
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function allowedUrlProvider(): \Iterator
    {
        yield 'Public Domain' => ['http://public.example.com'];
        yield 'Allowed Private IP' => ['http://192.168.1.1'];
        yield 'Allowed Localhost' => ['http://localhost'];
        yield 'Allowed IPv6 Localhost' => ['http://[::1]'];
        yield 'Domain to Allowed Private IP' => ['http://private.example.com'];
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function disallowedUrlProvider(): \Iterator
    {
        yield 'Disallowed Private IP' => ['http://192.168.1.1'];
        yield 'Different Private IP' => ['http://192.168.1.3'];
        yield 'Domain to Disallowed Private IP' => ['http://private.example.com'];
        yield 'Localhost when not allowed' => ['http://localhost'];
    }

    #[DataProvider('invalidUrlProvider')]
    public function testIsAllowedUrlThrowsExceptionForInvalidUrls(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->checkerWithMockedDns->isAllowedUrl($url);
    }
}
