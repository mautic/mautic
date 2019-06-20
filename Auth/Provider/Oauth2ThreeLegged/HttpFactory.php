<?php

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
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\CredentialsInterface;
use MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\AbstractClientFactory;

class HttpFactory extends AbstractClientFactory
{
    /**
     * {@inheritdoc}
     */
    protected function buildClient(CredentialsInterface $credentials): ClientInterface
    {
        $stack = HandlerStack::create();
        $grantType = new NullGrantType();

        $oAuth2 = new OAuth2Middleware($grantType);
        $oAuth2->setAccessTokenSigner(new BearerAuth());
        $oAuth2->setTokenPersistence(new MemoryTokenPersistence());
        $oAuth2->setAccessToken($credentials->getAccessToken());

        $stack->push($oAuth2);

        return new Client([
            'base_uri' => self::BASE_URI,
            'timeout'  => 0,
            'handler'  => $stack,
            'auth'     => 'oauth'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function credentialsAreConfigured(CredentialsInterface $credentials): bool
    {
        return $credentials->getClientId() && $credentials->getAccessToken();
    }
}