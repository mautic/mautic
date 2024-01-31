<?php

namespace Mautic\WebhookBundle\Tests\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\WebhookBundle\Http\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $parametersMock;

    /**
     * @var MockObject&GuzzleClient
     */
    private MockObject $httpClientMock;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parametersMock = $this->createMock(CoreParametersHelper::class);
        $this->httpClientMock = $this->createMock(GuzzleClient::class);
        $this->client         = new Client($this->parametersMock, $this->httpClientMock);
    }

    public function testPost(): void
    {
        $method  = 'POST';
        $url     = 'url';
        $payload = ['payload'];
        $siteUrl = 'siteUrl';
        $headers = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $siteUrl,
        ];

        $response = new Response();

        $this->parametersMock->expects($this->once())
            ->method('get')
            ->with('site_url')
            ->willReturn($siteUrl);

        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (Request $request) use ($method, $url, $headers, $payload) {
                $this->assertSame($method, $request->getMethod());
                $this->assertSame($url, $request->getUri()->getPath());

                foreach ($headers as $headerName => $headerValue) {
                    $header = $request->getHeader($headerName);
                    $this->assertSame($headerValue, $header[0]);
                }

                $this->assertSame(json_encode($payload), (string) $request->getBody());

                return true;
            }))
            ->willReturn($response);

        $this->assertEquals($response, $this->client->post($url, $payload));
    }
}
