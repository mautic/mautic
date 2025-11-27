<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CoreBundle\Helper\PrivateAddressChecker;
use PHPUnit\Framework\TestCase;

class PrivateAddressCheckerTest extends TestCase
{
    private PrivateAddressChecker $checker;
    private PrivateAddressChecker $checkerWithMockedDns;

    protected function setUp(): void
    {
        // Regular checker for IP tests
        $this->checker = new PrivateAddressChecker();

        // Checker with mocked DNS resolver for URL tests
        $this->checkerWithMockedDns = new PrivateAddressChecker(
            function (string $host) {
                return match ($host) {
                    'private.example.com' => ['192.168.1.1'],
                    'public.example.com'  => ['203.0.113.1'],
                    'api.example.com'     => ['8.8.8.8'],
                    'localhost'           => ['127.0.0.1'],
                    default               => false,
                };
            }
        );
    }

    /**
     * @dataProvider privateIpProvider
     */
    public function testIsPrivateIpReturnsTrue(string $ip): void
    {
        $this->assertTrue($this->checker->isPrivateIp($ip));
    }

    /**
     * @dataProvider publicIpProvider
     */
    public function testIsPrivateIpReturnsFalse(string $ip): void
    {
        $this->assertFalse($this->checker->isPrivateIp($ip));
    }

    /**
     * @dataProvider privateUrlProvider
     */
    public function testIsPrivateUrlReturnsTrue(string $url): void
    {
        $this->assertTrue($this->checkerWithMockedDns->isPrivateUrl($url));
    }

    /**
     * @dataProvider publicUrlProvider
     */
    public function testIsPrivateUrlReturnsFalse(string $url): void
    {
        $this->assertFalse($this->checkerWithMockedDns->isPrivateUrl($url));
    }

    /**
     * @return array<string, array{string}>
     */
    public function privateIpProvider(): array
    {
        return [
            'IPv4 Local'           => ['127.0.0.1'],
            'IPv4 Private Class A' => ['10.0.0.1'],
            'IPv4 Private Class B' => ['172.16.0.1'],
            'IPv4 Private Class C' => ['192.168.0.1'],
            'IPv4 Link Local'      => ['169.254.0.1'],
            'IPv6 Localhost'       => ['::1'],
            'IPv6 Unique Local'    => ['fc00::1'],
            'IPv6 Link Local'      => ['fe80::1'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function publicIpProvider(): array
    {
        return [
            'IPv4 Public'             => ['8.8.8.8'],
            'IPv4 Public Alternative' => ['203.0.113.1'],
            'IPv6 Public'             => ['2001:4860:4860::8888'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function privateUrlProvider(): array
    {
        return [
            'Localhost'                      => ['http://localhost'],
            'Localhost with port'            => ['http://localhost:8080'],
            'IPv4 Private'                   => ['http://192.168.1.1'],
            'IPv4 Private with path'         => ['https://10.0.0.1/path'],
            'IPv6 Localhost'                 => ['http://[::1]'],
            'IPv6 Private'                   => ['http://[fc00::1]'],
            'Domain resolving to private IP' => ['http://private.example.com'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function publicUrlProvider(): array
    {
        return [
            'Public Domain' => ['http://public.example.com'],
            'IPv4 Public'   => ['http://8.8.8.8'],
            'IPv6 Public'   => ['http://[2001:4860:4860::8888]'],
            'HTTPS URL'     => ['https://api.example.com/v1'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidUrlProvider(): array
    {
        return [
            'Empty URL'          => [''],
            'Invalid Format'     => ['not-a-url'],
            'Missing Protocol'   => ['example.com'],
            'Invalid Characters' => ['http://example.com\\invalid'],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testIsPrivateUrlThrowsExceptionForInvalidUrls(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->checkerWithMockedDns->isPrivateUrl($url);
    }

    public function testUnresolvableHostname(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL validation failed: Could not resolve hostname');
        $this->checkerWithMockedDns->isPrivateUrl('http://unresolvable.example.com');
    }

    public function testIsPrivateIpWithInvalidIPFormat(): void
    {
        $this->assertFalse($this->checker->isPrivateIp('invalid-ip'));
    }

    /**
     * @dataProvider edgeCaseUrlProvider
     */
    public function testEdgeCaseUrls(string $url, bool $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->checkerWithMockedDns->isPrivateUrl($url));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function edgeCaseUrlProvider(): array
    {
        return [
            'URL with Port'        => ['http://[::1]:8080', true],
            'IPv6 with Zone Index' => ['http://[fe80::1%eth0]', true],
            'IPv6 Full Format'     => ['http://[2001:0db8:85a3:0000:0000:8a2e:0370:7334]', false],
        ];
    }

    /**
     * @dataProvider allowedUrlProvider
     */
    public function testIsAllowedUrlReturnsTrue(string $url): void
    {
        $this->checkerWithMockedDns->setAllowedPrivateAddresses(['192.168.1.1', 'localhost', '::1']);
        $this->assertTrue($this->checkerWithMockedDns->isAllowedUrl($url));
    }

    /**
     * @dataProvider disallowedUrlProvider
     */
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
     * @return array<string, array{string}>
     */
    public function allowedUrlProvider(): array
    {
        return [
            'Public Domain'                => ['http://public.example.com'],
            'Allowed Private IP'           => ['http://192.168.1.1'],
            'Allowed Localhost'            => ['http://localhost'],
            'Allowed IPv6 Localhost'       => ['http://[::1]'],
            'Domain to Allowed Private IP' => ['http://private.example.com'], // Resolves to 192.168.1.1
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function disallowedUrlProvider(): array
    {
        return [
            'Disallowed Private IP'           => ['http://192.168.1.1'],
            'Different Private IP'            => ['http://192.168.1.3'],
            'Domain to Disallowed Private IP' => ['http://private.example.com'],
            'Localhost when not allowed'      => ['http://localhost'],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testIsAllowedUrlThrowsExceptionForInvalidUrls(string $url): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->checkerWithMockedDns->isAllowedUrl($url);
    }
}
