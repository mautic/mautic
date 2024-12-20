<?php

declare(strict_types=1);

namespace Mautic\UserBundle\EventListener;

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Security\Authenticator\Passport\Badge\AccessTokenBadge;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class ApiUserSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserProviderInterface $userProvider, private TokenPermissions $tokenPermissions)
    {
    }

    /**
     * Execute the authentication if authentication is oAuth and has no UserLoader set.
     * Sets permissions, and if user is not yet fetched - gets the user from TokenStorage, or creates one.
     */
    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(UserBadge::class)) {
            return;
        }

        $badge = $passport->getBadge(UserBadge::class);
        \assert($badge instanceof UserBadge);
        if (null !== $badge->getUserLoader()) {
            return;
        }

        if (!$passport->hasBadge(AccessTokenBadge::class)) {
            return;
        }

        $accessTokenBadge = $passport->getBadge(AccessTokenBadge::class);
        \assert($accessTokenBadge instanceof AccessTokenBadge);

        $badge->setUserLoader(function (string $userIdentifier) use ($passport, $accessTokenBadge): ?UserInterface {
            $user = null;

            try {
                $user = $this->userProvider->loadUserByIdentifier($userIdentifier);
            } catch (UserNotFoundException) {
                // ignore and get the user from oAuth2 token.
            }

            $accessToken = $accessTokenBadge->getAccessToken();
            if (null === $user) {
                $user = $this->tokenPermissions->setActivePermissionsOnAuthToken($accessToken);
            }

            if (null === $user) {
                return null;
            }

            $passport->addBadge(new AccessTokenBadge($accessToken, $user->getRoles()));

            return $user;
        });
    }

    /**
     * Transfers User instance from \Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions::setActivePermissionsOnAuthToken
     * to the token, to be authorized later.
     */
    public function onTokenCreated(AuthenticationTokenCreatedEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(AccessTokenBadge::class)) {
            return;
        }

        $accessTokenBadge = $passport->getBadge(AccessTokenBadge::class);
        \assert($accessTokenBadge instanceof AccessTokenBadge);

        $authenticatedToken = $event->getAuthenticatedToken();
        \assert($authenticatedToken instanceof OAuthToken);

        if (null !== $authenticatedToken->getUser()) {
            return;
        }

        $authenticatedToken->setUser($accessTokenBadge->getAccessToken()->getUser());
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class              => ['onCheckPassport', 2048],
            AuthenticationTokenCreatedEvent::class => 'onTokenCreated',
        ];
    }
}
