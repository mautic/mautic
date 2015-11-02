<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AuthenticationEvent
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
     * @param                $user
     * @param TokenInterface $token
     * @param UserProvider   $userProvider
     * @param Request        $request
     * @param bool           $loginCheck            Event executed from the mautic_sso_login_check route typically used as the SSO callback
     * @param string         $authenticatingService Service requesting authentication
     * @param null           $integrations
     */
    public function __construct(
        $user,
        TokenInterface $token,
        UserProvider $userProvider,
        Request $request,
        $loginCheck = false,
        $authenticatingService = null,
        $integrations = null
    ) {
        $this->token                 = $token;
        $this->user                  = $user;
        $this->userProvider          = $userProvider;
        $this->isFormLogin           = ($token instanceof UsernamePasswordToken);
        $this->integrations          = $integrations;
        $this->request               = $request;
        $this->isLoginCheck          = $loginCheck;
        $this->authenticatingService = $authenticatingService;
    }

    /**
     * Get user returned by username search
     *
     * @return string|User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user to be used after authentication
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
     * Get the token that has credentials, etc used to login
     *
     * @return PluginToken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get the username used
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->token->getUsername();
    }

    /**
     * Get user provider to find and/or create new users
     *
     * @return UserProvider
     */
    public function getUserProvider()
    {
        return $this->userProvider;
    }

    /**
     * Set if this user is successfully authenticated
     *
     * @param string    $service Service that authenticated the user; if using a Integration, it should match that of AbstractIntegration::getName();
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
     * Check if the user has been authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Get the service that authenticated the user
     *
     * @return string
     */
    public function getAuthenticatingService()
    {
        return $this->authenticatingService;
    }

    /**
     * Set a response such as a redirect
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
     * Get the response if set by the listener
     *
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Check if this is a form login authentication request or pre-auth
     *
     * @return bool
     */
    public function isFormLogin()
    {
        return $this->isFormLogin;
    }

    /**
     * Check if the event is executed as the result of accessing mautic_sso_login_check
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