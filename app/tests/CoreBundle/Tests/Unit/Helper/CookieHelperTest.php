<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CookieHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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

        $cookie = $this->cookieSetByOnResponse($cookieHelper);

        $this->assertStringContainsString('samesite=lax', (string) $cookie);
        $this->assertStringContainsString('secure', (string) $cookie);
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

        $cookie = $this->cookieSetByOnResponse($cookieHelper);

        $this->assertStringContainsString('samesite=lax', (string) $cookie);
        $this->assertStringNotContainsString('secure', (string) $cookie);
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

        $cookie = $this->cookieSetByOnResponse($cookieHelper);

        $this->assertStringContainsString('samesite=none', (string) $cookie);
        $this->assertStringContainsString('secure', (string) $cookie);
    }

    /**
     * Dispatches onResponse() against a real Response and returns the single cookie it set.
     *
     * Symfony 8 made HttpFoundation's InputBag final and tightened the value objects
     * around Response, so doubling Response and asserting on a mocked header bag no
     * longer works. Asserting on the cookie that actually lands is both simpler and a
     * closer test of the behaviour.
     */
    private function cookieSetByOnResponse(CookieHelper $cookieHelper): Cookie
    {
        $response = new Response();
        $event    = new ResponseEvent(
            new \AppKernel(MAUTIC_ENV, false),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $cookieHelper->onResponse($event);

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        return $cookies[0];
    }
}
