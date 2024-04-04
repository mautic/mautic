<?php

declare(strict_types=1);

namespace Mautic\Middleware\Tests;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\Middleware\HSTSMiddleware;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException as PHPUnitException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HSTSMiddlewareTest extends AbstractMauticTestCase
{
    public const HSTS_KEY = 'strict-transport-security';

    protected \ReflectionProperty $addHSTS;

    protected \ReflectionProperty $includeDubDomains;

    protected \ReflectionProperty $preload;

    protected HSTSMiddleware $middleware;

    protected \ReflectionClass $middlewareReflection;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware           = new HSTSMiddleware($this->client->getKernel());
        $this->middlewareReflection = new \ReflectionClass($this->middleware);

        $this->addHSTS = $this->middlewareReflection->getProperty('enableHSTS');
        $this->addHSTS->setAccessible(true);

        $this->includeDubDomains = $this->middlewareReflection->getProperty('includeDubDomains');
        $this->includeDubDomains->setAccessible(true);

        $this->preload = $this->middlewareReflection->getProperty('preload');
        $this->preload->setAccessible(true);
    }

    protected function testResponseHeaders(): void
    {
        $response = $this->getMiddlewareResponse();

        Assert::assertNotEmpty($response->headers);
    }

    public function testHSTSEnabled(): void
    {
        $this->setHSTS(true);
        $response = $this->getMiddlewareResponse();

        Assert::assertTrue(
            $response->headers->has(self::HSTS_KEY),
            'Strict-Transport-Security is enabled but is missing from the response headers'
        );
    }

    public function testHSTSDisabled(): void
    {
        $this->setHSTS(false);
        $response = $this->getMiddlewareResponse();

        Assert::assertFalse(
            $response->headers->has(self::HSTS_KEY),
            'Strict-Transport-Security is disabled but is present in response headers'
        );
    }

    public function testIncludeSubdomainsEnabled(): void
    {
        $needle = 'includeSubDomains';
        $this->setHSTS(true);
        $this->setIncludeDubDomainsValue(true);
        $response = $this->getMiddlewareResponse();

        Assert::assertStringContainsString(
            $needle,
            $response->headers->get(self::HSTS_KEY),
            'Option include Subdomains is enabled but is missing from the HSTS value'
        );
    }

    public function testIncludeSubdomainsDisabled(): void
    {
        $needle = 'includeSubDomains';
        $this->setHSTS(true);
        $this->setIncludeDubDomainsValue(false);

        $response = $this->getMiddlewareResponse();

        Assert::assertStringNotContainsStringIgnoringCase(
            $needle,
            $this->getHSTSValue($response),
            'Option include Subdomains is disabled but is present in HSTS value'
        );
    }

    public function testPreloadEnabled(): void
    {
        $needle = 'preload';
        $this->setHSTS(true);
        $this->setPreloadValue(true);

        $response = $this->getMiddlewareResponse();

        Assert::assertStringContainsString(
            $needle,
            $response->headers->get(self::HSTS_KEY),
            'Option preload is enabled but is missing from the HSTS value'
        );
    }

    public function testPreloadDisabled(): void
    {
        $needle = 'preload';
        $this->setHSTS(true);
        $this->setPreloadValue(false);

        $response = $this->getMiddlewareResponse();

        Assert::assertStringNotContainsStringIgnoringCase(
            $needle,
            $this->getHSTSValue($response),
            'Option preload is disabled but is present in HSTS value'
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function testExpireTime(): void
    {
        $this->setHSTS(true);
        $expireTimeValue = 12345;
        $expireTime      = $this->middlewareReflection->getProperty('expireTime');
        $expireTime->setAccessible(true);
        $expireTime->setValue($this->middleware, $expireTimeValue);

        $response = $this->getMiddlewareResponse();

        Assert::assertMatchesRegularExpression(
            '/max-age='.$expireTimeValue.'(; includeSubDomains)?/',
            $this->getHSTSValue($response),
            'Expire time does not match the configuration'
        );
    }

    private function setHSTS(bool $value): void
    {
        $this->addHSTS->setValue($this->middleware, $value);
    }

    private function setIncludeDubDomainsValue(bool $value): void
    {
        $this->includeDubDomains->setValue($this->middleware, $value);
    }

    private function setPreloadValue(bool $value): void
    {
        $this->preload->setValue($this->middleware, $value);
    }

    private function getHSTSValue(Response $response): string
    {
        return $response->headers->get(self::HSTS_KEY) ?? '';
    }

    private function getMiddlewareResponse(): Response
    {
        try {
            return $this->middleware->handle(Request::create('s/login', Request::METHOD_GET));
        } catch (\Exception $e) {
            throw new PHPUnitException($e->getMessage());
        }
    }
}
