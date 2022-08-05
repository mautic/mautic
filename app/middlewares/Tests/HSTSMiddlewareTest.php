<?php

declare(strict_types=1);

namespace Mautic\Middleware\Tests;

use Mautic\Middleware\ConfigAwareTrait;
use PHPUnit\Framework\Assert;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class HSTSMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    use ConfigAwareTrait;

    const HSTS_KEY = 'strict-transport-security';

    protected bool $enableHSTS;
    protected bool $includeDubDomains;
    protected int $expireTime;
    protected array $responseHeaders;
    protected string $HSTSValue;

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    protected function setUp(): void
    {
        $config                  = $this->getConfig();
        $this->enableHSTS        = array_key_exists('headers_sts', $config) && (bool) $config['headers_sts'];
        $this->includeDubDomains = array_key_exists('headers_sts_subdomains', $config) && (bool) $config['headers_sts_subdomains'];
        $this->expireTime        = $config['headers_sts_expire_time'] ?? 0;

        $client                = HttpClient::create();
        $response              = $client->request('GET', ($config['site_url'] ?? '').'s/login');
        $this->responseHeaders = $response->getHeaders();

        if ($this->enableHSTS) {
            $this->HSTSValue = $this->responseHeaders[self::HSTS_KEY][0] ?? '';

            if (isset($this->responseHeaders[self::HSTS_KEY]) && isset($this->responseHeaders[self::HSTS_KEY][0])) {
                $this->HSTSValue = $this->responseHeaders[self::HSTS_KEY][0];
            } else {
                throw new Exception('Strict-Transport-Security is enabled but is missing from the response headers');
            }
        }
    }

    public function testPresence(): void
    {
        if (!empty($this->responseHeaders) && $this->enableHSTS) {
            Assert::assertArrayHasKey(
                self::HSTS_KEY,
                $this->responseHeaders,
                'Strict-Transport-Security is enabled but is missing from the response headers'
            );
        } else {
            Assert::assertArrayNotHasKey(
                self::HSTS_KEY,
                $this->responseHeaders,
                'Strict-Transport-Security is disabled but is present in response headers'
            );
        }
    }

    public function testIncludeSubdomains(): void
    {
        if ($this->enableHSTS) {
            $needle = 'includeSubDomains';

            if ($this->includeDubDomains) {
                Assert::assertStringContainsString(
                    $needle,
                    $this->HSTSValue,
                    'Option include Subdomains is enabled but is missing from the HSTS value'
                );
            } else {
                Assert::assertStringNotContainsStringIgnoringCase(
                    $needle,
                    $this->HSTSValue,
                    'Option include Subdomains is disabled but is present in HSTS value'
                );
            }
        }
    }

    public function testExpireTime(): void
    {
        if ($this->enableHSTS) {
            Assert::assertMatchesRegularExpression(
                '/max-age='.$this->expireTime.'(; includeSubDomains)?/',
                $this->HSTSValue,
                'Expire time does not match the configuration'
            );
        }
    }
}
