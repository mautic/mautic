<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Firewall;

use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class AuthenticationListener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthenticationHandler
     */
    protected $authenticationHandler;

    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var
     */
    protected $providerKey;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var PermissionRepository
     */
    protected $permissionRepository;

    /**
     * @param AuthenticationHandler          $authenticationHandler
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param LoggerInterface                $logger
     * @param EventDispatcherInterface       $dispatcher
     * @param                                $providerKey
     * @param PermissionRepository           $permissionRepository
     */
    public function __construct(
        AuthenticationHandler $authenticationHandler,
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        $providerKey,
        PermissionRepository $permissionRepository
    ) {
        $this->tokenStorage          = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey           = $providerKey;
        $this->authenticationHandler = $authenticationHandler;
        $this->logger                = $logger;
        $this->dispatcher            = $dispatcher;
        $this->permissionRepository  = $permissionRepository;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->tokenStorage->getToken()) {
            $this->setActivePermissionsOnAuthToken();

            return;
        }

        $request = $event->getRequest();
        $token   = new PluginToken($this->providerKey, $request->get('integration', null));

        try {
            $authToken = $this->authenticationManager->authenticate($token);

            if ($authToken instanceof PluginToken) {
                $response = $authToken->getResponse();

                if ($authToken->isAuthenticated()) {
                    $this->tokenStorage->setToken($authToken);

                    $this->setActivePermissionsOnAuthToken();

                    if ('api' != $this->providerKey) {
                        $response = $this->onSuccess($request, $authToken, $response);
                    }
                } elseif (empty($response)) {
                    throw new AuthenticationException('mautic.user.auth.error.invalidlogin');
                }
            }
        } catch (AuthenticationException $exception) {
            if ('api' != $this->providerKey) {
                $response = $this->onFailure($request, $exception);
            }
        }

        if (!empty($response)) {
            $event->setResponse($response);
        }
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $failed
     *
     * @return Response
     */
    private function onFailure(Request $request, AuthenticationException $failed)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication request failed: %s', $failed->getMessage()));
        }

        $response = $this->authenticationHandler->onAuthenticationFailure($request, $failed);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Authentication Failure Handler did not return a Response.');
        }

        return $response;
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param Response|null  $response
     *
     * @return Response
     */
    private function onSuccess(Request $request, TokenInterface $token, Response $response = null)
    {
        if (null !== $this->logger) {
            $this->logger->info(sprintf('User "%s" has been authenticated successfully', $token->getUsername()));
        }

        $session = $request->getSession();
        $session->remove(Security::AUTHENTICATION_ERROR);

        if (null !== $this->dispatcher) {
            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
        }

        if (null === $response) {
            $response = $this->authenticationHandler->onAuthenticationSuccess($request, $token);

            if (!$response instanceof Response) {
                throw new \RuntimeException('Authentication Success Handler did not return a Response.');
            }
        }

        return $response;
    }

    /**
     * Set the active permissions on the current user.
     */
    private function setActivePermissionsOnAuthToken()
    {
        $token = $this->tokenStorage->getToken();
        $user  = $token->getUser();

        if (!$user->isAdmin() && empty($user->getActivePermissions())) {
            $activePermissions = $this->permissionRepository->getPermissionsByRole($user->getRole());

            $user->setActivePermissions($activePermissions);
        }

        $token->setUser($user);

        $this->tokenStorage->setToken($token);
    }
}
