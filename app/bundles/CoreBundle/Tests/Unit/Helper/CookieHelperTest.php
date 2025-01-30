<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CookieHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CookieHelperTest extends TestCase
{
    /**
     * @var RequestStack&MockObject
     */
    private MockObject $requestStackMock;

    /**
     * @var Request&MockObject
     */
    private MockObject $requestMock;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->requestMock      = $this->createMock(Request::class);
        $this->requestStackMock->method('getMainRequest')
            ->willReturn($this->requestMock);
    }

    /**
     * @testdox The helper is instantiated correctly when secure and contains samesite=lax
     *
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::__construct
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::setCookie
     */
    public function testSetCookieWhenSecure(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = true;
        $cookieHttp   = false;
        $requestStack = $this->requestStackMock;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $requestStack);
        $cookieName   = 'secureTest';

        $cookieHelper->setCookie($cookieName, 'test');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects(self::once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=lax', (string) $cookie);
                Assert::assertStringContainsString('secure', (string) $cookie);
            });

        $response          = $this->createMock(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createMock(Request::class);

        $event   = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }

    /**
     * @testdox The helper is instantiated correctly when not secure and contain samesite=lax
     *
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::__construct
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::setCookie
     */
    public function testSetCookieWhenNotSecure(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = false;
        $cookieHttp   = false;
        $requestStack = $this->requestStackMock;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $requestStack);
        $cookieName   = 'notSecureTest';

        $cookieHelper->setCookie($cookieName, 'test');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects(self::once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=lax', (string) $cookie);
                Assert::assertStringNotContainsString('secure', (string) $cookie);
            });

        $response          = $this->createMock(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createMock(Request::class);

        $event             = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }

    public function testSetCookieWhenSecureAndSameSiteNone(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = true;
        $cookieHttp   = false;
        $requestStack = $this->requestStackMock;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $requestStack);
        $cookieName   = 'samesite_test';

        $cookieHelper->setCookie(
            name: $cookieName,
            value: 'test',
            sameSite: Cookie::SAMESITE_NONE
        );

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects(self::once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=none', (string) $cookie);
                Assert::assertStringContainsString('secure', (string) $cookie);
            });

        $response          = $this->createMock(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createMock(Request::class);
        $event             = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }
}
