<?php

namespace Mautic\CoreBundle\Tests\unit\Helper;

use Mautic\CoreBundle\Helper\CookieHelper;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CookieHelper Test.
 */
class CookieHelperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStackMock;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    protected function setUp()
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('This test needs xdebug.');
        }
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->requestMock      = $this->createMock(Request::class);
        $this->requestStackMock->method('getCurrentRequest')
            ->willReturn($this->requestMock);
    }

    /**
     * @testdox The helper is instantiated correctly when secure and contains SameSite = None
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::__construct
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::setCookie
     */
    public function testSetCookieWhenSecure()
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = true;
        $cookieHttp   = false;
        $requestStack = $this->requestStackMock;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $requestStack);
        $cookieName   = 'secureTest';
        $cookieHelper->setCookie($cookieName, 'test');

        $cookie = $this->getCookie($cookieName);
        $this->assertContains('samesite=none', $cookie);
        $this->assertContains('secure', $cookie);
    }

    /**
     * @testdox The helper is instantiated correctly when not secure and does not contain SameSite = None
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::__construct
     * @covers \Mautic\CoreBundle\Helper\CookieHelper::setCookie
     */
    public function testSetCookieWhenNotSecure()
    {
        $cookiePath   = '/';
        $cookieDomain = 'https://test.test';
        $cookieSecure = false;
        $cookieHttp   = false;
        $requestStack = $this->requestStackMock;
        $cookieHelper = new CookieHelper($cookiePath, $cookieDomain, $cookieSecure, $cookieHttp, $requestStack);
        $cookieName   = 'notSecureTest';
        $cookieHelper->setCookie($cookieName, 'test');

        $cookie = $this->getCookie($cookieName);
        $this->assertNotContains('samesite=none', $cookie);
        $this->assertNotContains('secure', $cookie);
    }

    /**
     * Helper function to get cookie from header list.
     *
     * @param string $name
     *
     * @return string
     */
    private function getCookie($name)
    {
        $cookies = [];
        $headers = xdebug_get_headers();
        // see http://tools.ietf.org/html/rfc6265#section-4.1.1
        foreach ($headers as $header) {
            if (strpos($header, 'Set-Cookie: ') === 0) {
                $value = str_replace('&', urlencode('&'), substr($header, 12));
                parse_str(current(explode(';', $value, 1)), $pair);
                $cookies = array_merge_recursive($cookies, $pair);
            }
        }

        return $cookies[$name];
    }
}
