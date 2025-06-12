<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Auth\Provider\Oauth2ThreeLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory as OriginalHttpFactory;

/**
 * This mock class should just implement the interface.
 * In many custom plugins, the `HttpFactory` is used as argument in constructor injection. The use of class
 * `HttpFactory` should be replaced with AuthProviderInterface.
 */
class HttpFactory extends OriginalHttpFactory implements AuthProviderInterface
{
    private ClientInterface $client;

    public static function factory(MockHandler $handler): HttpFactory
    {
        $client              = new Client(['handler' => HandlerStack::create($handler)]);
        $httpFactory         = new self();
        $httpFactory->client = $client;

        return $httpFactory;
    }

    public function getAuthType(): string
    {
        return OriginalHttpFactory::NAME;
    }

    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        return $this->client;
    }
}
