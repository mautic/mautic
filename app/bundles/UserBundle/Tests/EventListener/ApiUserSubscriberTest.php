<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\EventListener;

use FOS\OAuthServerBundle\Model\AccessToken;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\OAuthServerBundle\Security\Authenticator\Passport\Badge\AccessTokenBadge;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\EventListener\ApiUserSubscriber;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Mautic\UserBundle\Security\Provider\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class ApiUserSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        self::assertSame([
            CheckPassportEvent::class              => ['onCheckPassport', 2048],
            AuthenticationTokenCreatedEvent::class => 'onTokenCreated',
        ], ApiUserSubscriber::getSubscribedEvents());
    }

    public function testIfAuthenticationHasNoUserInvolved(): void
    {
        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('hasBadge')
            ->with(UserBadge::class)
            ->willReturn(false);
        $passport->expects(self::never())
            ->method('getBadge');

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testIfAuthenticationAlreadySetUserLoader(): void
    {
        $userBadge = $this->createMock(UserBadge::class);
        $userBadge->expects(self::once())
            ->method('getUserLoader')
            ->willReturn(function () {});
        $userBadge->expects(self::never())
            ->method('setUserLoader');

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('hasBadge')
            ->with(UserBadge::class)
            ->willReturn(true);
        $passport->expects(self::once())
            ->method('getBadge')
            ->with(UserBadge::class)
            ->willReturn($userBadge);
        $passport->expects(self::never())
            ->method('addBadge');

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testIfAuthenticationIsNotOauthAuthentication(): void
    {
        $userBadge = $this->createMock(UserBadge::class);
        $userBadge->expects(self::once())
            ->method('getUserLoader')
            ->willReturn(null);
        $userBadge->expects(self::never())
            ->method('setUserLoader');

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::exactly(2))
            ->method('hasBadge')
            ->willReturnCallback(static function (string $className): bool {
                if (UserBadge::class === $className) {
                    return true;
                }

                if (AccessTokenBadge::class === $className) {
                    return false;
                }

                self::fail('Unknown badge class '.$className);
            });
        $passport->expects(self::once())
            ->method('getBadge')
            ->with(UserBadge::class)
            ->willReturn($userBadge);
        $passport->expects(self::never())
            ->method('addBadge');

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testIfOauthAuthenticationAndIdentifierIsNotFound(): void
    {
        $userIdentifier = 'The user';
        $userBadge      = $this->createMock(UserBadge::class);
        $userBadge->expects(self::once())
            ->method('getUserLoader')
            ->willReturn(null);

        $accessToken      = $this->createMock(AccessToken::class);
        $accessTokenBadge = $this->createMock(AccessTokenBadge::class);
        $accessTokenBadge->method('getAccessToken')->willReturn($accessToken);

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::exactly(2))
            ->method('hasBadge')
            ->willReturnCallback(static function (string $className): bool {
                if (UserBadge::class === $className) {
                    return true;
                }

                if (AccessTokenBadge::class === $className) {
                    return true;
                }

                self::fail('Unknown badge class '.$className);
            });
        $passport->expects(self::exactly(2))
            ->method('getBadge')
            ->willReturnCallback(function (string $className) use ($accessTokenBadge, $userBadge): BadgeInterface {
                if (UserBadge::class === $className) {
                    return $userBadge;
                }

                if (AccessTokenBadge::class === $className) {
                    return $accessTokenBadge;
                }

                self::fail('Unknown badge requested '.$className);
            });
        // Not changing any badges.
        $passport->expects(self::never())
            ->method('addBadge');

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($userIdentifier)
            ->willThrowException(new UserNotFoundException());
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::once())
            ->method('setActivePermissionsOnAuthToken')
            ->with($accessToken)
            ->willReturn(null);

        $userBadge->expects(self::once())
            ->method('setUserLoader')
            // After update to PHP 8.2 change return type to `null`.
            ->willReturnCallback(function (callable $userLoader) use ($userIdentifier): ?UserInterface {
                $loaderResult = $userLoader($userIdentifier);
                self::assertNull($loaderResult);

                return $loaderResult;
            });

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testIfOauthAuthenticationAndIdentifierIsUserFromLoader(): void
    {
        $userIdentifier = 'The user';
        $userRoles      = ['role' => 'test'];
        $userBadge      = $this->createMock(UserBadge::class);
        $userBadge->expects(self::once())
            ->method('getUserLoader')
            ->willReturn(null);

        $accessToken      = $this->createMock(AccessToken::class);
        $accessTokenBadge = $this->createMock(AccessTokenBadge::class);
        $accessTokenBadge->method('getAccessToken')->willReturn($accessToken);

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::exactly(2))
            ->method('hasBadge')
            ->willReturnCallback(static function (string $className): bool {
                if (UserBadge::class === $className) {
                    return true;
                }

                if (AccessTokenBadge::class === $className) {
                    return true;
                }

                self::fail('Unknown badge class '.$className);
            });
        $passport->expects(self::exactly(2))
            ->method('getBadge')
            ->willReturnCallback(function (string $className) use ($accessTokenBadge, $userBadge): BadgeInterface {
                if (UserBadge::class === $className) {
                    return $userBadge;
                }

                if (AccessTokenBadge::class === $className) {
                    return $accessTokenBadge;
                }

                self::fail('Unknown badge requested '.$className);
            });
        $passport->expects(self::once())
            ->method('addBadge')
            ->with(new AccessTokenBadge($accessToken, $userRoles));

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn($userRoles);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($userIdentifier)
            ->willReturn($user);
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $userBadge->expects(self::once())
            ->method('setUserLoader')
            ->willReturnCallback(function (callable $userLoader) use ($userIdentifier): User {
                $loaderResult = $userLoader($userIdentifier);
                self::assertNotNull($loaderResult);

                return $loaderResult;
            });

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testIfOauthAuthenticationAndIdentifierIsFromTokenPermisions(): void
    {
        $userIdentifier = 'The user';
        $userRoles      = ['role' => 'test'];
        $userBadge      = $this->createMock(UserBadge::class);
        $userBadge->expects(self::once())
            ->method('getUserLoader')
            ->willReturn(null);

        $accessToken      = $this->createMock(AccessToken::class);
        $accessTokenBadge = $this->createMock(AccessTokenBadge::class);
        $accessTokenBadge->method('getAccessToken')->willReturn($accessToken);

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::exactly(2))
            ->method('hasBadge')
            ->willReturnCallback(static function (string $className): bool {
                if (UserBadge::class === $className) {
                    return true;
                }

                if (AccessTokenBadge::class === $className) {
                    return true;
                }

                self::fail('Unknown badge class '.$className);
            });
        $passport->expects(self::exactly(2))
            ->method('getBadge')
            ->willReturnCallback(function (string $className) use ($accessTokenBadge, $userBadge): BadgeInterface {
                if (UserBadge::class === $className) {
                    return $userBadge;
                }

                if (AccessTokenBadge::class === $className) {
                    return $accessTokenBadge;
                }

                self::fail('Unknown badge requested '.$className);
            });
        $passport->expects(self::once())
            ->method('addBadge')
            ->with(new AccessTokenBadge($accessToken, $userRoles));

        $event = $this->createMock(CheckPassportEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);

        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn($userRoles);

        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with($userIdentifier)
            ->willThrowException(new UserNotFoundException());
        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::once())
            ->method('setActivePermissionsOnAuthToken')
            ->with($accessToken)
            ->willReturn($user);

        $userBadge->expects(self::once())
            ->method('setUserLoader')
            ->willReturnCallback(function (callable $userLoader) use ($userIdentifier): User {
                $loaderResult = $userLoader($userIdentifier);
                self::assertNotNull($loaderResult);

                return $loaderResult;
            });

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onCheckPassport($event);
    }

    public function testTokenCreatedNotOauthToken(): void
    {
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');

        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('hasBadge')
            ->with(AccessTokenBadge::class)
            ->willReturn(false);

        $event = $this->createMock(AuthenticationTokenCreatedEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);
        $event->expects(self::never())
            ->method('getAuthenticatedToken');

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onTokenCreated($event);
    }

    public function testTokenCreateOauthAlreadyHasAuthenticatedUser(): void
    {
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');

        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $accessTokenBadge = $this->createMock(AccessTokenBadge::class);

        $authenticatedToken = $this->createMock(OAuthToken::class);
        $authenticatedToken->method('getUser')->willReturn($this->createMock(UserInterface::class));
        // No user was replaced.
        $authenticatedToken->expects(self::never())
            ->method('setUser');

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('hasBadge')
            ->with(AccessTokenBadge::class)
            ->willReturn(true);
        $passport->method('getBadge')->with(AccessTokenBadge::class)->willReturn($accessTokenBadge);

        $event = $this->createMock(AuthenticationTokenCreatedEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);
        $event->expects(self::once())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken);

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onTokenCreated($event);
    }

    public function testTokenCreateOauthSetsAuthenticatedUser(): void
    {
        $userProvider = $this->createMock(UserProvider::class);
        $userProvider->expects(self::never())
            ->method('loadUserByIdentifier');

        $tokenPermissions = $this->createMock(TokenPermissions::class);
        $tokenPermissions->expects(self::never())
            ->method('setActivePermissionsOnAuthToken');

        $user = $this->createMock(UserInterface::class);

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getUser')->willReturn($user);

        $accessTokenBadge = $this->createMock(AccessTokenBadge::class);
        $accessTokenBadge->method('getAccessToken')->willReturn($accessToken);

        $authenticatedToken = $this->createMock(OAuthToken::class);
        $authenticatedToken->method('getUser')->willReturn(null);
        // Replace the user from oAuth token.
        $authenticatedToken->expects(self::once())
            ->method('setUser')
            ->with($user);

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('hasBadge')
            ->with(AccessTokenBadge::class)
            ->willReturn(true);
        $passport->method('getBadge')->with(AccessTokenBadge::class)->willReturn($accessTokenBadge);

        $event = $this->createMock(AuthenticationTokenCreatedEvent::class);
        $event->expects(self::once())
            ->method('getPassport')
            ->willReturn($passport);
        $event->expects(self::once())
            ->method('getAuthenticatedToken')
            ->willReturn($authenticatedToken);

        $subscriber = new ApiUserSubscriber($userProvider, $tokenPermissions);
        $subscriber->onTokenCreated($event);
    }
}
