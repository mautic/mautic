<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\PreUpdateCheckHelper;
use Mautic\CoreBundle\Helper\Update\Github\ReleaseParser;
use Mautic\CoreBundle\Helper\UpdateHelper;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class UpdateHelperConnectionErrorTest extends TestCase
{
    /**
     * @var MockObject&Client
     */
    private MockObject $client;

    /**
     * @var MockObject&ResponseInterface
     */
    private MockObject $response;

    private UpdateHelper $helper;

    protected function setUp(): void
    {
        // no getSystemPath expectation: a 404 short-circuits fetchPackage before the cache path is used
        $pathsHelper = $this->createStub(PathsHelper::class);

        $this->response = $this->createMock(ResponseInterface::class);
        $this->client   = $this->createMock(Client::class);

        $this->helper = new UpdateHelper(
            $pathsHelper,
            $this->createStub(Logger::class),
            $this->createStub(CoreParametersHelper::class),
            $this->client,
            $this->createStub(ReleaseParser::class),
            $this->createStub(PreUpdateCheckHelper::class)
        );
    }

    public function testConnectionErrorReturnsError(): void
    {
        $this->response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(404);

        $this->response->expects($this->never())
            ->method('getBody');

        $this->client->expects($this->once())
            ->method('request')
            ->with('GET', 'update.zip')
            ->willReturn($this->response);

        $result = $this->helper->fetchPackage('update.zip');

        $this->assertArrayHasKey('error', $result);
        $this->assertTrue($result['error']);
        $this->assertEquals('mautic.core.updater.error.fetching.package', $result['message']);
    }
}
