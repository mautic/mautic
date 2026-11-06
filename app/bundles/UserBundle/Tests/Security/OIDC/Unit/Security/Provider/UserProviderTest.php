<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Security\Provider;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Mautic\UserBundle\Security\OIDC\User\UserFactoryInterface;
use Mautic\UserBundle\Security\OIDC\UserProvider;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class UserProviderTest extends TestCase
{
    public function testLoadUserByUsernameLoadsUserBySubjectID(): void
    {
        $mauticUserProvider = $this->createMock(MauticUserProvider::class);
        $security           = $this->createStub(TokenStorageInterface::class);
        $linkerInterface    = $this->createMock(LinkerInterface::class);
        $userFactory        = $this->createStub(UserFactoryInterface::class);
        $user               = new User();

        $user->setUsername('test');

        $linkerInterface->expects($this->once())
            ->method('findLinkedUser')
            ->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByIdentifier('subjectId');
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByUsernameThrowsExceptionIfSubjectIDNotFound(): void
    {
        $mauticUserProvider = $this->createStub(MauticUserProvider::class);
        $security           = $this->createStub(TokenStorageInterface::class);
        $linkerInterface    = $this->createStub(LinkerInterface::class);
        $userFactory        = $this->createStub(UserFactoryInterface::class);
        $user               = new User();

        $user->setUsername('test');

        $this->expectException(UserNotFoundException::class);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $userProvider->loadUserByIdentifier('subjectId');
    }

    public function testRefreshUser(): void
    {
        $mauticUserProvider = $this->createMock(MauticUserProvider::class);
        $security           = $this->createStub(TokenStorageInterface::class);
        $linkerInterface    = $this->createStub(LinkerInterface::class);
        $userFactory        = $this->createStub(UserFactoryInterface::class);
        $user               = new User();

        $user->setUsername('test');

        $mauticUserProvider->expects($this->once())
            ->method('refreshUser')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->refreshUser($user);

        $this->assertSame($user, $loadedUser);
    }

    public function testSupportsClassIsTrueWhenMauticSupportsClass(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createStub(TokenStorageInterface::class);
        $linkerInterface     = $this->createStub(LinkerInterface::class);
        $userFactory         = $this->createStub(UserFactoryInterface::class);
        $user                = new User();

        $user->setUsername('test');

        $mauticUserProvider->expects($this->once())
            ->method('supportsClass')
            ->willReturn(true);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->supportsClass(User::class);

        $this->assertTrue($loadedUser);
    }

    public function testSupportsClassIsFalseWhenMauticDoesNotSupportClass(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createStub(TokenStorageInterface::class);
        $linkerInterface     = $this->createStub(LinkerInterface::class);
        $userFactory         = $this->createStub(UserFactoryInterface::class);
        $user                = new User();

        $user->setUsername('test');

        $mauticUserProvider->expects($this->once())
            ->method('supportsClass')
            ->willReturn(false);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->supportsClass(User::class);

        $this->assertFalse($loadedUser);
    }

    public function testLoadUserByCredentialsForLinkedUser(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createStub(TokenStorageInterface::class);
        $linkerInterface     = $this->createMock(LinkerInterface::class);
        $userFactory         = $this->createStub(UserFactoryInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId');

        $user->setUsername('test');

        $linkerInterface->expects($this->once())
            ->method('findLinkedUser')
            ->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByCredentialsForUnlinkedUserLinksToCurrentUser(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createMock(TokenStorageInterface::class);
        $linkerInterface     = $this->createMock(LinkerInterface::class);
        $userFactory         = $this->createStub(UserFactoryInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId');

        $user->setUsername('test');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $security->method('getToken')->willReturn($token);

        $linkerInterface->expects($this->once())
            ->method('findLinkedUser')
            ->willReturn(null);

        $linkerInterface->expects($this->once())
            ->method('linkToUser')
            ->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByCredentialsForUnlinkedUserCreatesNewUser(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createMock(TokenStorageInterface::class);
        $linkerInterface     = $this->createMock(LinkerInterface::class);
        $userFactory         = $this->createMock(UserFactoryInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId', 'email@mautic.com');

        $user->setUsername('test');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $security->method('getToken')->willReturn($token);

        $linkerInterface->expects($this->once())
            ->method('findLinkedUser')
            ->willReturn(null);

        $userFactory->method('create')->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('saveUser')
            ->willReturn($user);

        $linkerInterface->expects($this->once())
            ->method('linkToUser')
            ->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test')
            ->willReturn($user);

        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        $this->assertSame($user, $loadedUser);
    }
}
