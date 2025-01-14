<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface;
use Mautic\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth1a headers.
 */
class HttpFactory implements AuthProviderInterface
{
    const NAME = 'oauth1a_two_legged';

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private $initializedClients = [];

    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param CredentialsInterface|AuthCredentialsInterface $credentials
     * @param AuthConfigInterface                           $config
     *
     * @throws PluginNotConfiguredException
     */
    public function getClient(AuthCredentialsInterface $credentials, ?AuthConfigInterface $config = null): ClientInterface
    {
        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getConsumerKey()])) {
            return $this->initializedClients[$credentials->getConsumerKey()];
        }

        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Oauth1a Credentials or URL is missing');
        }

        $this->initializedClients[$credentials->getConsumerKey()] = $this->buildClient($credentials);

        return $this->initializedClients[$credentials->getConsumerKey()];
    }

    /**
     * @return Client
     */
    private function buildClient(CredentialsInterface $credentials)
    {
        $stack = HandlerStack::create();
        $stack->push($this->createOauth1($credentials));

        return new Client(
            [
                'handler'  => $stack,
                'base_uri' => $credentials->getAuthUrl(),
                'auth'     => 'oauth',
            ]
        );
    }

    /**
     * @return Oauth1
     */
    private function createOauth1(CredentialsInterface $credentials)
    {
        $config = [
            'consumer_key'    => $credentials->getConsumerKey(),
            'consumer_secret' => $credentials->getConsumerSecret(),
        ];

        if ($credentials->getToken() && $credentials->getTokenSecret()) {
            $config['token']        = $credentials->getToken();
            $config['token_secret'] = $credentials->getTokenSecret();
        }

        return new Oauth1($config);
    }

    /**
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials)
    {
        return !empty($credentials->getAuthUrl()) && !empty($credentials->getConsumerKey()) && !empty($credentials->getConsumerSecret());
    }
}
