<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CookieHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
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
        $cookie       = $cookieHelper->setCookie($cookieName, 'test');

        if (function_exists('xdebug_get_headers')) {
            $cookie = $this->getCookie($cookieName);
        }

        Assert::assertStringContainsString('samesite=lax', $cookie);
        Assert::assertStringContainsString('secure', $cookie);
    }

    /**
     * @testdox The helper is instantiated correctly when not secure and does not contain samesite=lax
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
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
        $cookie       = $cookieHelper->setCookie($cookieName, 'test');

        if (function_exists('xdebug_get_headers')) {
            $cookie = $this->getCookie($cookieName);
        }

        Assert::assertStringNotContainsString('SameSite=None', $cookie);
        Assert::assertStringNotContainsString('secure', $cookie);
    }

    /**
     * Helper function to get cookie from header list.
     */
    private function getCookie(string $name): string
    {
        $cookies = [];
        $headers = xdebug_get_headers();
        // see http://tools.ietf.org/html/rfc6265#section-4.1.1
        foreach ($headers as $header) {
            if (0 === strpos($header, 'Set-Cookie: ')) {
                $value = str_replace('&', urlencode('&'), substr($header, 12));
                parse_str(current(explode(';', $value, 1)), $pair);
                $cookies = array_merge_recursive($cookies, $pair);
            }
        }

        return $cookies[$name];
    }
}
