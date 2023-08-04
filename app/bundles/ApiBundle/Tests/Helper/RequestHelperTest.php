<?php

namespace Mautic\ApiBundle\Tests\Helper;

use Mautic\ApiBundle\Helper\RequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class RequestHelperTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
    }

    public function testIsBasicAuthWithValidBasicAuth()
    {
        $this->request->headers = new HeaderBag(['Authorization' => 'Basic dXNlcm5hbWU6cGFzc3dvcmQ=']);

        $this->assertTrue(RequestHelper::hasBasicAuth($this->request));
    }

    public function testIsBasicAuthWithInvalidBasicAuth()
    {
        $this->request->headers = new HeaderBag(['Authorization' => 'Invalid Basic Auth value']);

        $this->assertFalse(RequestHelper::hasBasicAuth($this->request));
    }

    public function testIsBasicAuthWithMissingBasicAuth()
    {
        $this->request->headers = new HeaderBag([]);

        $this->assertFalse(RequestHelper::hasBasicAuth($this->request));
    }

    public function testIsApiRequestWithOauthUrl()
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/oauth/v2/token');

        $this->assertTrue(RequestHelper::isApiRequest($this->request));
    }

    public function testIsApiRequestWithApiUrl()
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/api/contacts');

        $this->assertTrue(RequestHelper::isApiRequest($this->request));
    }

    public function testIsNotApiRequest()
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/s/dashboard');

        $this->assertFalse(RequestHelper::isApiRequest($this->request));
    }
}
