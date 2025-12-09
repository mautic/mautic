<?php

namespace Mautic\WebhookBundle\Tests\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PrivateAddressChecker;
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
        $this->client         = new Client($this->parametersMock, $this->httpClientMock, new PrivateAddressChecker());
    }

    public function testPost(): void
    {
        $method  = 'POST';
        $url     = 'https://8.8.8.8';
        $payload = ['payload'];
        $secret  = 'secret123';
        $siteUrl = 'siteUrl';

        // Calculate the expected signature the same way as the Client class
        $jsonPayload       = json_encode($payload);
        $expectedSignature = base64_encode(hash_hmac('sha256', $jsonPayload, $secret, true));

        $headers = [
            'Content-Type'      => 'application/json',
            'X-Origin-Base-URL' => $siteUrl,
            'Webhook-Signature' => $expectedSignature,
        ];

        $response = new Response();

        $matcher = $this->exactly(2);
        $this->parametersMock->expects($matcher)
            ->method('get')
            ->willReturnCallback(function (string $parameter) use ($matcher, $siteUrl) {
                if (1 === $matcher->getInvocationCount()) {
                    $this->assertSame('site_url', $parameter);

                    return $siteUrl;
                }
                if (2 === $matcher->getInvocationCount()) {
                    $this->assertSame('webhook_allowed_private_addresses', $parameter);

                    return [];
                }
                throw new \RuntimeException('Unexpected method call');
            });

        $this->httpClientMock->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (Request $request) use ($method, $url, $headers, $payload) {
                $this->assertSame($method, $request->getMethod());
                $this->assertSame($url, (string) $request->getUri());

                foreach ($headers as $headerName => $headerValue) {
                    $header = $request->getHeader($headerName);
                    $this->assertSame($headerValue, $header[0]);
                }

                $this->assertSame(json_encode($payload), (string) $request->getBody());

                return true;
            }))
            ->willReturn($response);

        $this->assertEquals($response, $this->client->post($url, $payload, $secret));
    }
}
