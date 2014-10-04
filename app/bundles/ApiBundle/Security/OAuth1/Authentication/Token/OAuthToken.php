<?php

namespace Mautic\ApiBundle\Security\OAuth1\Authentication\Token;

/**
 * Class OAuthToken
 *
 * @package Mautic\ApiBundle\Security\Token
 */
class OAuthToken extends \Bazinga\OAuthServerBundle\Security\Authentification\Token\OAuthToken
{

    /**
     * @author William DURAND <william.durand1@gmail.com>
     * @param string $requestUrl A request URL.
     */
    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }
}
