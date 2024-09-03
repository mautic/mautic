<?php

namespace Mautic\UserBundle\Security\Firewall;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ApiBundle\Entity\oAuth2\AccessToken;
use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class AuthenticationListener
{
    /**
     * @param string|mixed $providerKey
     */
    public function __construct(
        private AuthenticationHandler $authenticationHandler,
        private TokenStorageInterface $tokenStorage,
        private AuthenticationManagerInterface $authenticationManager,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        private $providerKey,
        private PermissionRepository $permissionRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(RequestEvent $event): void
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

                if (null !== $authToken->getUser()) {
                    $this->tokenStorage->setToken($authToken);

                    $this->setActivePermissionsOnAuthToken();

                    if ('api' !== $this->providerKey) {
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

    private function onFailure(Request $request, AuthenticationException $failed): Response
    {
        $this->logger->info(sprintf('Authentication request failed: %s', $failed->getMessage()));

        return $this->authenticationHandler->onAuthenticationFailure($request, $failed);
    }

    private function onSuccess(Request $request, TokenInterface $token, Response $response = null): Response
    {
        $this->logger->info(sprintf('User "%s" has been authenticated successfully', $token->getUserIdentifier()));

        $session = $request->getSession();
        $session->remove(Security::AUTHENTICATION_ERROR);

        $loginEvent = new InteractiveLoginEvent($request, $token);
        $this->dispatcher->dispatch($loginEvent, SecurityEvents::INTERACTIVE_LOGIN);

        if (null === $response) {
            $response = $this->authenticationHandler->onAuthenticationSuccess($request, $token);
        }

        return $response;
    }

    /**
     * Set the active permissions on the current user.
     */
    private function setActivePermissionsOnAuthToken(): void
    {
        $token = $this->tokenStorage->getToken();
        /** @var User|null $user */
        $user  = $token->getUser();

        // If no user associated with a token, it's a client credentials grant type. Handle accordingly.
        if (is_null($user)) {
            $user = $this->assignRoleFromToken($token);
        }

        if (!$user->isAdmin() && empty($user->getActivePermissions())) {
            $activePermissions = $this->permissionRepository->getPermissionsByRole($user->getRole());

            $user->setActivePermissions($activePermissions);
        }

        $token->setUser($user);

        $this->tokenStorage->setToken($token);
    }

    /**
     * Handle permission for Client Credential grant type.
     */
    private function assignRoleFromToken(TokenInterface $token): User
    {
        $token = $token->getToken();

        /** @var AccessToken $accessToken */
        $accessToken = $this->entityManager->getRepository(AccessToken::class)->findOneBy(['token' => $token]);

        $role = $accessToken->getClient()->getRole();

        // Create a pseudo user and assign the role
        $user = new User();
        $user->setRole($role);

        // Set for the audit log and the entity's "created by user" metadata which takes the first and last name
        $user->setFirstName($accessToken->getClient()->getName());
        $user->setLastName(sprintf('[%s]', $accessToken->getClient()->getId()));
        $user->setUsername($user->getName());
        defined('MAUTIC_AUDITLOG_USER') || define('MAUTIC_AUDITLOG_USER', $user->getName());

        return $user;
    }
}
