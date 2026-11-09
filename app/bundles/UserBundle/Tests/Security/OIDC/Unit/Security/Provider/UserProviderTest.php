<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Security\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\LinkerInterface;
use Mautic\UserBundle\Security\OIDC\User\UserFactoryInterface;
use Mautic\UserBundle\Security\OIDC\UserProvider;
use Mautic\UserBundle\Security\Provider\UserProvider as MauticUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
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

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByCredentialsForUnlinkedUserCreatesNewUser(): void
    {
        $mauticUserProvider  = $this->createMock(MauticUserProvider::class);
        $security            = $this->createMock(TokenStorageInterface::class);
        $linkerInterface     = $this->createMock(LinkerInterface::class);
        $userFactory         = $this->createMock(UserFactoryInterface::class);
        $entityManager       = $this->createMock(EntityManagerInterface::class);
        $passwordHasher      = $this->createMock(UserPasswordHasherInterface::class);
        $user                = new User();
        $credentials         = new UserCredentials('subjectId', 'email@mautic.com');

        $user->setUsername('test');
        $user->setEmail('email@mautic.com');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $security->method('getToken')->willReturn($token);

        $linkerInterface->expects($this->once())
            ->method('findLinkedUser')
            ->willReturn(null);

        $userFactory->method('create')->willReturn($user);

        // Expect password to be hashed and set
        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $this->anything())
            ->willReturn('hashed_password');

        // Expect user to be persisted and flushed
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $entityManager->expects($this->once())
            ->method('flush');

        $linkerInterface->expects($this->once())
            ->method('linkToUser')
            ->willReturn($user);

        $mauticUserProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('test')
            ->willReturn($user);

        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $userProvider = new UserProvider($linkerInterface, $userFactory, $mauticUserProvider, $security, $entityManager, $passwordHasher, $dispatcher);
        $loadedUser   = $userProvider->loadUserByCredentials($credentials);

        $this->assertSame($user, $loadedUser);
    }
}
