<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Auth\Provider\Oauth1aTwoLegged;

use Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\CredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use PHPUnit\Framework\TestCase;

class HttpFactoryTest extends TestCase
{
    public function testType(): void
    {
        $this->assertEquals('oauth1a_two_legged', (new HttpFactory())->getAuthType());
    }

    public function testGetClientWithEmptyCredentials(): void
    {
        $credentials = $this->createMock(CredentialsInterface::class);
        $httpFactory = new HttpFactory();
        $this->expectException(PluginNotConfiguredException::class);
        $httpFactory->getClient($credentials);
    }

    public function testGetClientWithFullCredentials(): void
    {
        $credentials = $this->createMock(CredentialsInterface::class);
        $credentials->method('getConsumerKey')->willReturn('ConsumerKeyValue');
        $credentials->method('getConsumerSecret')->willReturn('ConsumerSecretValue');
        $credentials->method('getToken')->willReturn('TokenValue');
        $credentials->method('getTokenSecret')->willReturn('TokenSecretValue');
        $credentials->method('getAuthUrl')->willReturn('AuthUrlValue');
        $httpFactory = new HttpFactory();
        $client      = $httpFactory->getClient($credentials);
        $config      = $client->getConfig();

        $this->assertSame('oauth', $config['auth']);
        $this->assertSame('AuthUrlValue', $config['base_uri']->getPath());
        $this->assertTrue($config['handler']->hasHandler());
    }
}
