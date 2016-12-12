<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PreAuthAuthenticator implements AuthenticationProviderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var
     */
    protected $providerKey;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var null|requestStack
     */
    protected $requestStack;

    /**
     * @param IntegrationHelper        $integrationHelper
     * @param EventDispatcherInterface $dispatcher
     * @param RequestStack             $requestStack
     * @param UserProviderInterface    $userProvider
     * @param                          $providerKey
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        EventDispatcherInterface $dispatcher,
        RequestStack $requestStack,
        UserProviderInterface $userProvider,
        $providerKey
    ) {
        $this->dispatcher        = $dispatcher;
        $this->providerKey       = $providerKey;
        $this->userProvider      = $userProvider;
        $this->integrationHelper = $integrationHelper;
        $this->requestStack      = $requestStack;
    }

    /**
     * @param TokenInterface $token
     *
     * @return Response|PluginToken
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        $user                  = $token->getUser();
        $authenticatingService = $token->getAuthenticatingService();
        $response              = null;
        $request               = $this->requestStack->getCurrentRequest();

        if (!$user instanceof User) {
            $authenticated = false;

            // Try authenticating with a plugin
            if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_AUTHENTICATION)) {
                $integrations = $this->integrationHelper->getIntegrationObjects($authenticatingService, ['sso_service'], false, null, true);

                $loginCheck = ('mautic_sso_login_check' == $request->attributes->get('_route'));
                $authEvent  = new AuthenticationEvent(
                    null,
                    $token,
                    $this->userProvider,
                    $request,
                    $loginCheck,
                    $authenticatingService,
                    $integrations
                );
                $this->dispatcher->dispatch(UserEvents::USER_PRE_AUTHENTICATION, $authEvent);

                if ($authenticated = $authEvent->isAuthenticated()) {
                    $eventToken = $authEvent->getToken();
                    if ($eventToken !== $token) {
                        // A custom token has been set by the plugin so just return it

                        return $eventToken;
                    }

                    $user                  = $authEvent->getUser();
                    $authenticatingService = $authEvent->getAuthenticatingService();
                }

                $response = $authEvent->getResponse();

                if (!$authenticated && $loginCheck && !$response) {
                    // Set an empty JSON response
                    $response = new JsonResponse([]);
                }
            }

            if (!$authenticated && empty($response)) {
                throw new AuthenticationException('mautic.user.auth.error.invalidlogin');
            }
        }

        return new PluginToken(
            $this->providerKey,
            $authenticatingService,
            $user,
            ($user instanceof User) ? $user->getPassword() : '',
            ($user instanceof User) ? $user->getRoles() : [],
            $response
        );
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
}
