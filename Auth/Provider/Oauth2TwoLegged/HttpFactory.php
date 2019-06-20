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

namespace MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\GrantType\GrantTypeInterface;
use kamermans\OAuth2\GrantType\PasswordCredentials;
use kamermans\OAuth2\GrantType\RefreshToken;
use kamermans\OAuth2\OAuth2Middleware;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Exception\PluginNotConfiguredException;

/**
 * Factory for building HTTP clients that will sign the requests with Oauth2 headers.
 * Based on Guzzle OAuth 2.0 Subscriber - kamermans/guzzle-oauth2-subscriber package
 * @see https://github.com/kamermans/guzzle-oauth2-subscriber
 */
class HttpFactory implements AuthProviderInterface
{
    const NAME = 'oauth2_two_legged';

    /**
     * @var PasswordCredentialsGrantInterface|ClientCredentialsGrantInterface
     */
    private $credentials;

    /**
     * @var Client
     */
    private $reAuthClient;

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
     * @param PasswordCredentialsGrantInterface|ClientCredentialsGrantInterface $credentials
     *
     * @return ClientInterface
     * @throws PluginNotConfiguredException
     */
    public function getClient($credentials): ClientInterface
    {
        if (!$this->credentialsAreConfigured($credentials)) {
            throw new PluginNotConfiguredException('Authorization URL, client ID or client secret is missing');
        }

        // Return cached initialized client if there is one.
        if (!empty($this->initializedClients[$credentials->getClientId()])) {
            return $this->initializedClients[$credentials->getClientId()];
        }

        $this->credentials     = $credentials;
        $reAuthConfig          = $this->getReAuthConfig();
        $accessTokenGrantType  = $this->getGrantType($reAuthConfig);
        $refreshTokenGrantType = new RefreshToken($this->getReAuthClient(), $reAuthConfig);

        $oauth = new OAuth2Middleware($accessTokenGrantType, $refreshTokenGrantType);
        $stack = HandlerStack::create();
        $stack->push($oauth);

        return $this->initializedClients[$credentials->getClientId()] = new Client(
            [
                'handler' => $stack,
                'auth'    => 'oauth',
            ]
        );
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials)
    {
        return !empty($credentials->getAuthorizationUrl()) && !empty($credentials->getClientId()) && !empty($credentials->getClientSecret());
    }

    /**
     * @return ClientInterface
     */
    private function getReAuthClient(): ClientInterface
    {
        if ($this->reAuthClient) {
            return $this->reAuthClient;
        }

        $this->reAuthClient = new Client(
            [
                'base_url' => $this->credentials->getAuthorizationUrl(),
            ]
        );

        return $this->reAuthClient;
    }

    /**
     * @return array
     */
    private function getReAuthConfig(): array
    {
        $config = [
            'client_id'     => $this->credentials->getClientId(),
            'client_secret' => $this->credentials->getClientSecret(),
            'scope'         => $this->credentials->getScope(),
            'state'         => $this->credentials->getState(),
        ];

        if ($this->credentials instanceof ClientCredentialsGrantInterface) {
            return $config;
        }

        $config['username'] = $this->credentials->getUsername();
        $config['password'] = $this->credentials->getPassword();

        return $config;
    }

    /**
     * @param array $config
     *
     * @return GrantTypeInterface
     */
    private function getGrantType(array $config): GrantTypeInterface
    {
        if ($this->credentials instanceof ClientCredentialsGrantInterface) {
            return new ClientCredentials($this->getReAuthClient(), $config);
        }

        return new PasswordCredentials($this->getReAuthClient(), $config);
    }
}