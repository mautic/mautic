<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\OAuth1\Authentication\Provider;

use Bazinga\OAuthServerBundle\Security\Authentification\Token\OAuthToken;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class OAuthProvider.
 */
class OAuthProvider extends \Bazinga\OAuthServerBundle\Security\Authentification\Provider\OAuthProvider
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        $translator = $this->factory->getTranslator();

        $requestParameters = $token->getRequestParameters();
        $requestMethod     = $token->getRequestMethod();
        $requestUrl        = $token->getRequestUrl();

        if ($this->serverService->validateRequest($requestParameters, $requestMethod, $requestUrl)) {
            $accessToken = $this->tokenProvider->loadAccessTokenByToken($requestParameters['oauth_token']);
            $user        = $accessToken->getUser();

            if (null !== $user) {
                //Recreate token to include user roles in order to be able to avoid CSRF checks with forms
                $token = new OAuthToken($user->getRoles());
                $token->setRequestParameters($requestParameters);
                $token->setRequestMethod($requestMethod);
                $token->setRequestUrl($requestUrl);
                $token->setAuthenticated(true);
                $token->setUser($user);
            }

            return $token;
        }

        throw new AuthenticationException($translator->trans('mautic.api.oauth.auth.failed'));
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuthToken;
    }
}
