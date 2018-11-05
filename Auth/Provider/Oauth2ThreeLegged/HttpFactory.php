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
use kamermans\OAuth2\GrantType\NullGrantType;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Signer\AccessToken\BearerAuth;
use MauticPlugin\IntegrationsBundle\Auth\Provider\AuthProviderInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\OAuth2\Persistence\FileTokenPersistence;
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
        $grantType = new NullGrantType();

        $oAuth2 = new OAuth2Middleware($grantType);
        $oAuth2->setAccessTokenSigner(new BearerAuth());
        $oAuth2->setAccessToken([
            'access_token' => $credentials->getAccessToken(),
        ]);

        $stack->push($oAuth2);

        return new Client([
            'base_uri' => $credentials->getBaseUri(),
            'timeout'  => 0,
            'handler'  => $stack,
            'auth'     => 'oauth'
        ]);
    }

    /**
     * @param CredentialsInterface $credentials
     *
     * @return bool
     */
    private function credentialsAreConfigured(CredentialsInterface $credentials)
    {
        return $credentials->getAuthorizationUrl() && $credentials->getClientId() && $credentials->getAccessToken() && $credentials->getBaseUri();
    }
}