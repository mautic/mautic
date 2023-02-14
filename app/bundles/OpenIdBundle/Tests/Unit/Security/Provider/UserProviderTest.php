<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Security\Provider;

use Mautic\OpenIdBundle\DTO\UserCredentials;
use Mautic\OpenIdBundle\Entity\SubjectId;
use Mautic\OpenIdBundle\Factory\UserFactoryInterface;
use Mautic\OpenIdBundle\Security\Provider\UserProvider;
use Mautic\OpenIdBundle\Service\LinkerInterface;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use PHPStan\Testing\TestCase;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;

final class UserProviderTest extends TestCase
{
    public function testLoadUserByUsernameLoadsUserBySubjectID(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();

        $user->setUsername('test');
        $subjectId->setUser($user);

        $linkerInterface->expects(self::once())
            ->method('findLinkedUser')
            ->willReturn($user);

        $mauticUserProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByUsername('subjectId');

        self::assertSame($user, $loadedUser);
    }

    public function testLoadUserByUsernameThrowsExceptionIfSubjectIDNotFound(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();

        $user->setUsername('test');
        $subjectId->setUser($user);

        self::expectException(UsernameNotFoundException::class);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $userProvider->loadUserByUsername('subjectId');
    }

    public function testRefreshUser(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();

        $user->setUsername('test');
        $subjectId->setUser($user);

        $mauticUserProvider->expects(self::once())
            ->method('refreshUser')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->refreshUser($user);

        self::assertSame($user, $loadedUser);
    }

    public function testSupportsClassIsTrueWhenMauticSupportsClass(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();

        $user->setUsername('test');
        $subjectId->setUser($user);

        $mauticUserProvider->expects(self::once())
            ->method('supportsClass')
            ->willReturn(true);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->supportsClass(User::class);

        self::assertTrue($loadedUser);
    }

    public function testSupportsClassIsFalseWhenMauticDoesNotSupportClass(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();

        $user->setUsername('test');
        $subjectId->setUser($user);

        $mauticUserProvider->expects(self::once())
            ->method('supportsClass')
            ->willReturn(false);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->supportsClass(User::class);

        self::assertFalse($loadedUser);
    }

    public function testLoadUserByCredentialsForLinkedUser(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $subjectId           = new SubjectId();
        $user                = new User();
        $credentials         = new UserCredentials('subjectId');

        $user->setUsername('test');
        $subjectId->setUser($user);

        $linkerInterface->expects(self::once())
            ->method('findLinkedUser')
            ->willReturn($user);

        $mauticUserProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        self::assertSame($user, $loadedUser);
    }

    public function testLoadUserByCredentialsForUnlinkedUserLinksToCurrentUser(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId');

        $user->setUsername('test');

        $security->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $mauticUserProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        self::assertSame($user, $loadedUser);
    }

    public function testLoadUserByCredentialsForUnlinkedUserCreatesNewUser(): void
    {
        $mauticUserProvider  = self::createMock(MauticUserProvider::class);
        $security            = self::createMock(Security::class);
        $linkerInterface     = self::createMock(LinkerInterface::class);
        $userFactory         = self::createMock(UserFactoryInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId', 'email@mautic.com');

        $user->setUsername('test');

        $mauticUserProvider->expects(self::once())
            ->method('saveUser')
            ->willReturn($user);

        $mauticUserProvider->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        self::assertSame($user, $loadedUser);
    }
}
