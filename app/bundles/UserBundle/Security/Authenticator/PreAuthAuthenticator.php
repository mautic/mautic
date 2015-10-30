<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authenticator;

use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PreAuthAuthenticator implements AuthenticationProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var
     */
    private $providerKey;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @param EventDispatcherInterface     $dispatcher
     * @param UserProviderInterface        $userProvider
     * @param                              $providerKey
     */
    public function __construct(EventDispatcherInterface $dispatcher, UserProviderInterface $userProvider, $providerKey)
    {
        $this->dispatcher   = $dispatcher;
        $this->providerKey  = $providerKey;
        $this->userProvider = $userProvider;
    }

    /**
     * @param TokenInterface        $token
     *
     * @return UsernamePasswordToken
     */
    public function authenticate(TokenInterface $token)
    {
        $authenticated = false;

        // Try authenticating with a plugin
        if ($this->dispatcher->hasListeners(UserEvents::USER_AUTHENTICATION)) {
            $authEvent = new AuthenticationEvent(null, $token, $this->userProvider);
            $this->dispatcher->dispatch(UserEvents::USER_AUTHENTICATION, $authEvent);

            if ($authenticated = $authEvent->isAuthenticated()) {
                $user = $authEvent->getUser();
            }
        }

        if ($authenticated) {

            return new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $this->providerKey,
                $user->getRoles()
            );
        }
die('test');
        throw new AuthenticationException('Invalid username or password');
    }

    /**
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PluginToken && $token->getProviderKey() === $this->providerKey;
    }

    /**
     * @param Request $request
     * @param         $username
     * @param         $password
     * @param         $providerKey
     *
     * @return UsernamePasswordToken
     */
    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }
}