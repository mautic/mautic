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

class CredentialsFactory
{
    /**
     * @param string $clientId
     * @param string $authorizationUri
     * @param string $redirectUri
     *
     * @return Credentials
     */
    public function create(string $clientId, string $authorizationUri, string $redirectUri): Credentials
    {
        $credentials = new Credentials();
        $credentials->setClientId($clientId);
        $credentials->setAuthorizationUrl($authorizationUri);
        $credentials->setRedirectUri($redirectUri);

        return $credentials;
    }
}