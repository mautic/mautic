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
use Mautic\UserBundle\Security\Provider\UserProvider;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AuthenticationEvent
 */
class AuthenticationEvent extends Event
{
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
     * @param null|User      $user
     * @param TokenInterface $token
     * @param UserProvider   $userProvider
     */
    public function __construct($user, TokenInterface $token, UserProvider $userProvider )
    {
        $this->token        = $token;
        $this->user         = $user;
        $this->userProvider = $userProvider;
        $this->isFormLogin  = ($token instanceof UsernamePasswordToken);
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
     * @param bool|true $createIfNotExists  If true, the user will be created if it does not exist
     */
    public function setUser(User $user, $createIfNotExists = true)
    {
        if ($createIfNotExists) {
            $this->userProvider->createUserIfNotExists($user);
        }

        $this->user = $user;
    }

    /**
     * Get the token that has credentials, etc used to login
     *
     * @return MauticUserToken
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
     * @param bool|true $authenticated
     */
    public function setIsAuthentication($authenticated = true)
    {
        $this->isAuthenticated = $authenticated;
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
     * Check if this is a form login authentication request or pre-auth
     *
     * @return bool
     */
    public function isFormLogin()
    {
        return $this->isFormLogin;
    }
}