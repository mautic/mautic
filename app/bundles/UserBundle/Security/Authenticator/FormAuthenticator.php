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
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FormAuthenticator implements SimpleFormAuthenticatorInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param UserPasswordEncoderInterface $encoder
     * @param EventDispatcherInterface     $dispatcher
     */
    public function __construct(UserPasswordEncoderInterface $encoder, EventDispatcherInterface $dispatcher)
    {
        $this->encoder     = $encoder;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param                       $providerKey
     *
     * @return UsernamePasswordToken
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $authenticated = false;
        $user          = null;

        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
            if ($token instanceof UsernamePasswordToken) {
                $authenticated = $this->encoder->isPasswordValid($user, $token->getCredentials());
            }
        } catch (UsernameNotFoundException $e) {
        }

        if (!$authenticated) {
            // Try authenticating with a plugin
            if ($this->dispatcher->hasListeners(UserEvents::USER_AUTHENTICATION)) {
                $authEvent = new AuthenticationEvent($user, $token, $userProvider);
                $this->dispatcher->dispatch(UserEvents::USER_AUTHENTICATION, $authEvent);

                if ($authenticated = $authEvent->isAuthenticated()) {
                    $user = $authEvent->getUser();
                }
            }
        }

        if ($authenticated) {

            return new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $providerKey,
                $user->getRoles()
            );
        }

        throw new AuthenticationException('Invalid username or password');
    }

    /**
     * @param TokenInterface $token
     * @param                $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return ($token instanceof UsernamePasswordToken) && $token->getProviderKey() === $providerKey;
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