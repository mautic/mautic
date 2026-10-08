<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Helper;

use Mautic\ApiBundle\Helper\RequestHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
final class RequestHelperTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Request
     */
    private \PHPUnit\Framework\MockObject\MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
    }

    public function testIsBasicAuthWithValidBasicAuth(): void
    {
        $this->assertTrue(RequestHelper::hasBasicAuth(
            $this->requestWithAuthorization('Basic dXNlcm5hbWU6cGFzc3dvcmQ=')
        ));
    }

    public function testIsBasicAuthWithInvalidBasicAuth(): void
    {
        $this->assertFalse(RequestHelper::hasBasicAuth(
            $this->requestWithAuthorization('Invalid Basic Auth value')
        ));
    }

    public function testIsBasicAuthWithMissingBasicAuth(): void
    {
        $this->assertFalse(RequestHelper::hasBasicAuth(new Request()));
    }

    public function testIsApiRequestWithOauthUrl(): void
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/oauth/v2/token');

        $this->assertTrue(RequestHelper::isApiRequest($this->request));
    }

    public function testIsApiRequestWithApiUrl(): void
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/api/contacts');

        $this->assertTrue(RequestHelper::isApiRequest($this->request));
    }

    public function testIsNotApiRequest(): void
    {
        $this->request->expects($this->once())
            ->method('getRequestUri')
            ->willReturn('/s/dashboard');

        $this->assertFalse(RequestHelper::isApiRequest($this->request));
    }

    /**
     * A real request rather than the mock: hasBasicAuth() reads the header bag, and
     * assigning one onto a doubled Request does not survive on Symfony 8.
     */
    private function requestWithAuthorization(string $value): Request
    {
        $request = new Request();
        $request->headers->set('Authorization', $value);

        return $request;
    }
}
