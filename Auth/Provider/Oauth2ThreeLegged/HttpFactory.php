<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth1a headers.
 * Based on Guzzle OAuth 2.0 Subscriber - kamermans/guzzle-oauth2-subscriber package
 * @see https://github.com/kamermans/guzzle-oauth2-subscriber
 */
class HttpFactory implements AuthProviderInterface
{
    const NAME = 'oauth3a_three_legged';

    /**
     * Cache of initialized clients.
     *
     * @var Client[]
     */
    private $initializedClients = [];

    /**
     * @return string
     */
    public function getAuthType(): string
    {
        return self::NAME;
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return ClientInterface
     * @throws PluginNotConfiguredException
     *
     * @todo cache key must be based on url too
     */
    public function getClient($credentials): ClientInterface
    {
        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getClientId()])) {
            return $this->initializedClients[$credentials->getClientId()];
        }

        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Oauth2 ClientId or URL is missing');
        }

        $this->initializedClients[$credentials->getClientId()] = $this->buildClient($credentials);

        return $this->initializedClients[$credentials->getClientId()];
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return Client
     */
    private function buildClient(CredentialsInterface $credentials)
    {
        $stack = HandlerStack::create();
        $stack->push($this->createOAuth2($credentials));

        return new Client([
            'handler'  => $stack,
            'auth'     => 'oauth'
        ]);
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return OAuth2Middleware
     */
    private function createOAuth2(CredentialsInterface $credentials)
    {
        // Authorization client - this is used to request OAuth access tokens
        $authClient = new Client([
            // URL for access_token request
            'base_uri' => $credentials->getAuthorizationUrl(),
        ]);

        $config = [
            "client_id" => $credentials->getClientId(),
        ];

        $grantType = new ClientCredentials($authClient, $config);
        $oAuth2 = new OAuth2Middleware($grantType);

        return $oAuth2;
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials)
    {
        return !empty($credentials->getAuthorizationUrl()) && !empty($credentials->getClientId());
    }
}