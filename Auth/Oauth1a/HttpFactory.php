<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Oauth1a;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use MauticPlugin\IntegrationsBundle\Auth\Oauth1a\CredentialsInterface;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth1a headers.
 */
class HttpFactory
{
    /**
     * Cache of initialized clients.
     * 
     * @var Client[]
     */
    private $initializedClients = [];

    /**
     * @param CredentialsInteface $credentials
     * 
     * @return Client
     * 
     * @throws PluginNotConfiguredException
     */
    public function getClient(CredentialsInterface $credentials)
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
     * @param CredentialsInterface $credentials
     * 
     * @return Client
     */
    private function buildClient(CredentialsInterface $credentials)
    {
        $stack = HandlerStack::create();
        $stack->push($this->createOauth1($credentials));

        return new Client([
            'handler'  => $stack,
            'base_uri' => $credentials->getAuthUrl(),
            'auth'     => 'oauth'
        ]);
    }

    /**
     * @param CredentialsInterface $credentials
     * 
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
     * @param CredentialsInterface $credentials
     * 
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials)
    {
        return !empty($credentials->getAuthUrl()) && !empty($credentials->getConsumerKey()) && !empty($credentials->getConsumerSecret());
    }
}
