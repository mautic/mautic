<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\AuthenticationHandler;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\Security\Authenticator\Passport\Badge\PluginBadge;
use Mautic\UserBundle\UserEvents;
use OAuth2\OAuth2;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Merges in the implementation of the \Mautic\UserBundle\Security\Firewall\AuthenticationListener
 * and \Mautic\UserBundle\Security\Authenticator\PreAuthAuthenticator.
 */
final class PluginAuthenticator extends AbstractAuthenticator
{
    public function __construct(private TokenPermissions $tokenPermissions, private EventDispatcherInterface $dispatcher, private IntegrationHelper $integrationHelper, private UserProviderInterface $userProvider, private AuthenticationHandler $authenticationHandler, private OAuth2 $oAuth2, private LoggerInterface $logger, private string $firewallName)
    {
    }

    public function supports(Request $request): ?bool
    {
        // Pass to oAuth2 if the token is present, but try to execute in case the Request does not look like oAuth2.
        return null === $this->oAuth2->getBearerToken($request) ? null : false;
    }

    public function authenticate(Request $request): Passport
    {
        $authenticatingService = $request->get('integration');
        \assert(null === $authenticatingService || is_string($authenticatingService));
        $token = new PluginToken($this->firewallName, $authenticatingService);

        $user          = null;
        $response      = null;
        $authenticated = false;

        // Try authenticating with a plugin
        if ($this->dispatcher->hasListeners(UserEvents::USER_PRE_AUTHENTICATION)) {
            $integrations = $this->integrationHelper->getIntegrationObjects($authenticatingService, ['sso_service'], false, null, true);

            $loginCheck = ('mautic_sso_login_check' === $request->attributes->get('_route'));
            $authEvent  = new AuthenticationEvent(
                null,
                $token,
                $this->userProvider,
                $request,
                $loginCheck,
                $authenticatingService,
                $integrations
            );
            $this->dispatcher->dispatch($authEvent, UserEvents::USER_PRE_AUTHENTICATION);

            if ($authenticated = $authEvent->isAuthenticated()) {
                $eventToken            = $authEvent->getToken();
                $authenticatingService = $authEvent->getAuthenticatingService();

                if ($eventToken !== $token) {
                    // Merge app/bundles/UserBundle/Security/Authenticator/PreAuthAuthenticator.php:68
                    // and app/bundles/UserBundle/Security/Firewall/AuthenticationListener.php:61
                    return new SelfValidatingPassport(
                        new UserBadge($eventToken->getUserIdentifier(), function () use ($eventToken): UserInterface {
                            return $eventToken->getUser();
                        }),
                        [new PluginBadge($eventToken, null, $authenticatingService)]
                    );
                }

                $user = $authEvent->getUser();
            }

            $response = $authEvent->getResponse();

            if (!$authenticated && $loginCheck && null === $response) {
                // Set an empty JSON response
                $response = new JsonResponse([]);
            }
        }

        // First part is from app/bundles/UserBundle/Security/Firewall/AuthenticationListener.php:61
        // plus app/bundles/UserBundle/Security/Authenticator/PreAuthAuthenticator.php:47
        // AND is from app/bundles/UserBundle/Security/Authenticator/PreAuthAuthenticator.php:86
        if (!$user instanceof User && !$authenticated && null === $response) {
            throw new AuthenticationException('mautic.user.auth.error.invalidlogin');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $user instanceof User ? $user->getUserIdentifier() : $user,
                function (string $userIdentifier) use ($user): UserInterface {
                    if ($user instanceof User) {
                        return $user;
                    }

                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                }
            ),
            [new PluginBadge(null, $response, $authenticatingService)]
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $apiBadgeBadge = $passport->getBadge(PluginBadge::class);
        \assert($apiBadgeBadge instanceof PluginBadge);
        $userBadge = $passport->getBadge(UserBadge::class);
        \assert($userBadge instanceof UserBadge);

        // A custom token has been set by the plugin so just return it
        if (null === $token = $apiBadgeBadge->getPreAuthenticatedToken()) {
            $user  = $userBadge->getUser();
            $token = new PluginToken(
                $this->firewallName,
                $apiBadgeBadge->getAuthenticatingService(),
                $user,
                ($user instanceof User) ? $user->getPassword() : '',
                ($user instanceof User) ? $user->getRoles() : [],
                $apiBadgeBadge->getPluginResponse()
            );
        }

        $this->tokenPermissions->setActivePermissionsOnAuthToken($token);

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if (!$token instanceof PluginToken) {
            // Maybe this need to be replaced with assert, but as no tests cover this, an exception will be noticed earlier.
            throw new \RuntimeException('Token is not a PluginToken');
        }

        if ('api' === $this->firewallName) {
            return $token->getResponse();
        }

        $this->logger->info(sprintf('User "%s" has been authenticated successfully', $token->getUserIdentifier()));

        $session = $request->getSession();
        $session->remove(Security::AUTHENTICATION_ERROR);

        $loginEvent = new InteractiveLoginEvent($request, $token);
        $this->dispatcher->dispatch($loginEvent, SecurityEvents::INTERACTIVE_LOGIN);

        $response = null;
        if (null === $token->getResponse()) {
            $response = $this->authenticationHandler->onAuthenticationSuccess($request, $token);
        }

        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->info(sprintf('Authentication request failed: %s', $exception->getMessage()));

        // Gets app/bundles/UserBundle/Security/Firewall/AuthenticationListener.php:74 and till the end of the method referenced.
        if ('api' === $this->firewallName) {
            // Continue with another authentication.
            return null;
        }

        return $this->authenticationHandler->onAuthenticationFailure($request, $exception);
    }
}
