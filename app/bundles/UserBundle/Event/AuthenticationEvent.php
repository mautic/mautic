<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class AuthenticationEvent.
 */
class AuthenticationEvent extends Event
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $user;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var bool
     */
    protected $isAuthenticated = false;

    /**
     * @var bool
     */
    protected $forceFailedAuthentication = false;

    /**
     * @var UserProvider
     */
    protected $userProvider;

    /**
     * @var bool
     */
    protected $isFormLogin;

    /**
     * @var bool
     */
    protected $isLoginCheck;

    /**
     * @var string Service that authenticated the user
     */
    protected $authenticatingService;

    /**
     * @var
     */
    protected $integrations;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Message to display to user if there is a failed authentication.
     *
     * @var string
     */
    protected $failedAuthMessage;

    /**
     * @param                       $user
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param Request               $request
     * @param bool                  $loginCheck            Event executed from the mautic_sso_login_check route typically used as the SSO callback
     * @param string                $authenticatingService Service Service requesting authentication
     * @param null                  $integrations
     */
    public function __construct(
        $user,
        TokenInterface $token,
        UserProviderInterface $userProvider,
        Request $request,
        $loginCheck = false,
        $authenticatingService = null,
        $integrations = null
    ) {
        $this->token = $token;
        $this->user  = $user;

        $this->isFormLogin           = ($token instanceof UsernamePasswordToken);
        $this->integrations          = $integrations;
        $this->request               = $request;
        $this->isLoginCheck          = $loginCheck;
        $this->authenticatingService = $authenticatingService;

        if ($userProvider instanceof ChainUserProvider) {
            // Chain of user providers so let's find Mautic's
            $providers = $userProvider->getProviders();
            foreach ($providers as $provider) {
                if ($provider instanceof UserProvider) {
                    $userProvider = $provider;

                    break;
                }
            }
        }

        $this->userProvider = $userProvider;
    }

    /**
     * Get user returned by username search.
     *
     * @return string|User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user to be used after authentication.
     *
     * @param User      $user
     * @param bool|true $saveUser
     * @param bool|true $createIfNotExists If true, the user will be created if it does not exist
     */
    public function setUser(User $user, $saveUser = true, $createIfNotExists = true)
    {
        if ($saveUser) {
            $user = $this->userProvider->saveUser($user, $createIfNotExists);
        }

        $this->user = $user;
    }

    /**
     * Get the token that has credentials, etc used to login.
     *
     * @return PluginToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param                $service
     * @param TokenInterface $token
     */
    public function setToken($service, TokenInterface $token)
    {
        $this->token                 = $token;
        $this->authenticatingService = $service;
        $this->isAuthenticated       = $token->isAuthenticated();

        $this->stopPropagation();
    }

    /**
     * Get the username used.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->token->getUsername();
    }

    /**
     * Get user provider to find and/or create new users.
     *
     * @return UserProvider
     */
    public function getUserProvider()
    {
        return $this->userProvider;
    }

    /**
     * Set if this user is successfully authenticated.
     *
     * @param string    $service           Service that authenticated the user; if using a Integration, it should match that of AbstractIntegration::getName();
     * @param User|null $user
     * @param bool|true $createIfNotExists
     */
    public function setIsAuthenticated($service, User $user = null, $createIfNotExists = true)
    {
        $this->authenticatingService = $service;
        $this->isAuthenticated       = true;

        if (null !== $user) {
            $this->setUser($user, $createIfNotExists);
        }

        // Authenticated so stop propagation
        $this->stopPropagation();
    }

    /**
     * Check if the user has been authenticated.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Prevent any other authentication method from authorizing the user.
     * Mainly used to prevent a form login from trying to auth with the given password for a local user (think two-factor requirements).
     */
    public function setIsFailedAuthentication()
    {
        $this->forceFailedAuthentication = true;

        // Authenticated so stop propagation
        $this->stopPropagation();
    }

    /**
     * Set the message to display to the user for failing auth.
     *
     * @param $message
     */
    public function setFailedAuthenticationMessage($message)
    {
        $this->failedAuthMessage = $message;
    }

    /**
     * Returns message to display to user for failing auth.
     *
     * @return string
     */
    public function getFailedAuthenticationMessage()
    {
        return $this->failedAuthMessage;
    }

    /**
     * Returns true if a plugin has forcefully failed authentication.
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->forceFailedAuthentication;
    }

    /**
     * Get the service that authenticated the user.
     *
     * @return string
     */
    public function getAuthenticatingService()
    {
        return $this->authenticatingService;
    }

    /**
     * Set a response such as a redirect.
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        // A response has been requested so stop propagation
        $this->stopPropagation();
    }

    /**
     * Get the response if set by the listener.
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Check if this is a form login authentication request or pre-auth.
     *
     * @return bool
     */
    public function isFormLogin()
    {
        return $this->isFormLogin;
    }

    /**
     * Check if the event is executed as the result of accessing mautic_sso_login_check.
     *
     * @return bool
     */
    public function isLoginCheck()
    {
        return $this->isLoginCheck;
    }

    /**
     * @param $integrationName
     *
     * @return AbstractIntegration|bool
     */
    public function getIntegration($integrationName)
    {
        return (isset($this->integrations[$integrationName])) ? $this->integrations[$integrationName] : false;
    }
}
