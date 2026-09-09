<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CookieHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(CookieHelper::class)]
#[AllowMockObjectsWithoutExpectations]
final class CookieHelperTest extends TestCase
{
    /**
     * @var MockObject&RequestStack
     */
    private MockObject $requestStackMock;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->requestStackMock->method('getMainRequest')
            ->willReturn($this->createStub(Request::class));
    }

    #[TestDox('The helper is instantiated correctly when secure and contains samesite=lax')]
    public function testSetCookieWhenSecure(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = true;
        $cookieHttp   = false;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $this->requestStackMock);
        $cookieName   = 'secureTest';

        $cookieHelper->setCookie($cookieName, 'test');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects($this->once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=lax', (string) $cookie);
                Assert::assertStringContainsString('secure', (string) $cookie);
            });

        $response          = $this->createStub(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createStub(Request::class);

        $event   = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }

    #[TestDox('The helper is instantiated correctly when not secure and contain samesite=lax')]
    public function testSetCookieWhenNotSecure(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = false;
        $cookieHttp   = false;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $this->requestStackMock);
        $cookieName   = 'notSecureTest';

        $cookieHelper->setCookie($cookieName, 'test');

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects($this->once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=lax', (string) $cookie);
                Assert::assertStringNotContainsString('secure', (string) $cookie);
            });

        $response          = $this->createStub(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createStub(Request::class);

        $event             = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }

    public function testSetCookieWhenSecureAndSameSiteNone(): void
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = true;
        $cookieHttp   = false;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $this->requestStackMock);
        $cookieName   = 'samesite_test';

        $cookieHelper->setCookie(
            name: $cookieName,
            value: 'test',
            sameSite: Cookie::SAMESITE_NONE
        );

        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects($this->once())
            ->method('setCookie')
            ->willReturnCallback(static function (Cookie $cookie): void {
                Assert::assertStringContainsString('samesite=none', (string) $cookie);
                Assert::assertStringContainsString('secure', (string) $cookie);
            });

        $response          = $this->createStub(Response::class);
        $response->headers = $headers;
        $kernel            = new \AppKernel(MAUTIC_ENV, false);
        $request           = $this->createStub(Request::class);
        $event             = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHelper->onResponse($event);
    }
}
