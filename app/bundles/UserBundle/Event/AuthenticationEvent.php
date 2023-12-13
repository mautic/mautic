<?php

namespace Mautic\UserBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationEvent extends Event
{
    /**
     * @var Response
     */
    protected $response;

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
    protected \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider;

    protected bool $isFormLogin;

    /**
     * Message to display to user if there is a failed authentication.
     *
     * @var string
     */
    protected $failedAuthMessage;

    /**
     * @param string|User|null                $user
     * @param bool                            $isLoginCheck          Event executed from the mautic_sso_login_check route typically used as the SSO callback
     * @param string                          $authenticatingService Service Service requesting authentication
     * @param array<AbstractIntegration>|null $integrations
     */
    public function __construct(
        protected $user,
        protected TokenInterface $token,
        UserProviderInterface $userProvider,
        protected Request $request,
        protected $isLoginCheck = false,
        protected $authenticatingService = null,
        protected $integrations = null
    ) {
        $this->isFormLogin           = $token instanceof UsernamePasswordToken;

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
     * @return string|User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user to be used after authentication.
     *
     * @param bool|true $saveUser
     * @param bool|true $createIfNotExists If true, the user will be created if it does not exist
     */
    public function setUser(User $user, $saveUser = true, $createIfNotExists = true): void
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

    public function setToken($service, TokenInterface $token): void
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
        return $this->token->getUserIdentifier();
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
     * @param bool|true $createIfNotExists
     */
    public function setIsAuthenticated($service, User $user = null, $createIfNotExists = true): void
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
    public function setIsFailedAuthentication(): void
    {
        $this->forceFailedAuthentication = true;

        // Authenticated so stop propagation
        $this->stopPropagation();
    }

    /**
     * Set the message to display to the user for failing auth.
     */
    public function setFailedAuthenticationMessage($message): void
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
     */
    public function setResponse(Response $response): void
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
     */
    public function isFormLogin(): bool
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
     * @return AbstractIntegration|bool
     */
    public function getIntegration($integrationName)
    {
        return $this->integrations[$integrationName] ?? false;
    }
}
