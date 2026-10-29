<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Provider\Oauth1aTwoLegged;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\HandlerStack;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\CredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use PHPUnit\Framework\TestCase;

final class HttpFactoryTest extends TestCase
{
    public function testType(): void
    {
        $this->assertSame('oauth1a_two_legged', new HttpFactory()->getAuthType());
    }

    public function testGetClientWithEmptyCredentials(): void
    {
        $credentials = $this->createStub(CredentialsInterface::class);
        $httpFactory = new HttpFactory();
        $this->expectException(PluginNotConfiguredException::class);
        $httpFactory->getClient($credentials);
    }

    public function testGetClientWithFullCredentials(): void
    {
        $credentials = $this->createMock(CredentialsInterface::class);
        $credentials->expects($this->exactly(5))->method('getConsumerKey')->willReturn('ConsumerKeyValue');
        $credentials->expects($this->exactly(2))->method('getConsumerSecret')->willReturn('ConsumerSecretValue');
        $credentials->method('getToken')->willReturn('TokenValue');
        $credentials->method('getTokenSecret')->willReturn('TokenSecretValue');
        $credentials->method('getAuthUrl')->willReturn('AuthUrlValue');
        $httpFactory = new HttpFactory();
        $client      = $httpFactory->getClient($credentials);
        $config      = $client->getConfig(); /** @phpstan-ignore-line Deprecated. Must be refactored for Guzzle 8 */
        $this->assertSame('oauth', $config['auth']);
        /** @var UriInterface $baseUri */
        $baseUri = $config['base_uri'];
        $this->assertSame('AuthUrlValue', $baseUri->getPath());
        /** @var HandlerStack $handler */
        $handler = $config['handler'];
        $this->assertTrue($handler->hasHandler());
    }
}
