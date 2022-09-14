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

class CookieHelperTest extends TestCase
{
    /**
     * @var RequestStack|MockObject
     */
    private $requestStackMock;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $requestMock            = $this->createMock(Request::class);
        $this->requestStackMock->method('getMasterRequest')
            ->willReturn($requestMock);
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
        $event             = $this->createMock(ResponseEvent::class);
        $event->expects(self::once())
            ->method('getResponse')
            ->willReturn($response);

        $cookieHelper->onResponse($event);
    }

    /**
     * @testdox The helper is instantiated correctly when not secure and does not contain samesite=lax
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
                Assert::assertStringNotContainsString('samesite=lax', (string) $cookie);
                Assert::assertStringNotContainsString('secure', (string) $cookie);
            });

        $response          = $this->createMock(Response::class);
        $response->headers = $headers;
        $event             = $this->createMock(ResponseEvent::class);
        $event->expects(self::once())
            ->method('getResponse')
            ->willReturn($response);

        $cookieHelper->onResponse($event);
    }
}
