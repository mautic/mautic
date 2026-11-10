<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Security\Provider;

use Mautic\UserBundle\Entity\PermissionRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\UserEvent;
use Mautic\UserBundle\Security\Provider\UserProvider;
use Mautic\UserBundle\UserEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserProviderTest extends TestCase
{
    public function testCreateUserPersistsNewIdentityWithPasswordAndSaveEvents(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('createQueryBuilder');
        $persistedUser = null;
        $repository->method('saveEntity')->willReturnCallback(static function (User $user) use (&$persistedUser): void {
            $persistedUser = clone $user;
        });
        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed-password');
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(UserEvents::USER_PRE_SAVE, static function (UserEvent $event): void {
            $event->getUser()->setTimezone('Europe/Prague');
        });
        $savedEvent = null;
        $dispatcher->addListener(UserEvents::USER_POST_SAVE, static function (UserEvent $event) use (&$savedEvent): void {
            $savedEvent = $event;
        });

        $provider = new UserProvider($repository, $this->createStub(PermissionRepository::class), $dispatcher, $hasher, $this->createStub(TranslatorInterface::class));
        $user = $this->newUser();
        $result = $provider->createUser($user);

        $this->assertSame($user, $result);
        $this->assertInstanceOf(User::class, $persistedUser);
        $this->assertSame('new@example.com', $persistedUser->getEmail());
        $this->assertSame('hashed-password', $persistedUser->getPassword());
        $this->assertSame('Europe/Prague', $persistedUser->getTimezone());
        $this->assertInstanceOf(UserEvent::class, $savedEvent);
        $this->assertTrue($savedEvent->isNew());
        $this->assertSame($user, $savedEvent->getUser());
    }

    public function testCreateUserStillValidatesRequiredFields(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveEntity');
        $provider = new UserProvider($repository, $this->createStub(PermissionRepository::class), new EventDispatcher(), $this->createStub(UserPasswordHasherInterface::class), $this->createStub(TranslatorInterface::class));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('mautic.integration.sso.error.no_role');
        $provider->createUser(new User());
    }

    public function testCreateUserRejectsPersistedAccount(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('saveEntity');
        $provider = new UserProvider($repository, $this->createStub(PermissionRepository::class), new EventDispatcher(), $this->createStub(UserPasswordHasherInterface::class), $this->createStub(TranslatorInterface::class));
        $user = $this->newUser();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, 42);

        $this->expectException(\InvalidArgumentException::class);
        $provider->createUser($user);
    }

    public function testSaveUserStillResolvesExistingAccountForAuthenticationPlugins(): void
    {
        $repository = $this->createStub(UserRepository::class);
        $provider = $this->getMockBuilder(UserProvider::class)
            ->setConstructorArgs([$repository, $this->createStub(PermissionRepository::class), new EventDispatcher(), $this->createStub(UserPasswordHasherInterface::class), $this->createStub(TranslatorInterface::class)])
            ->onlyMethods(['loadUserByIdentifier'])
            ->getMock();
        $existingUser = $this->newUser();
        $existingUser->setPassword('existing-password');
        (new \ReflectionProperty(User::class, 'id'))->setValue($existingUser, 42);
        $provider->method('loadUserByIdentifier')->willReturn($existingUser);

        $result = $provider->saveUser($this->newUser());

        $this->assertSame($existingUser, $result);
        $this->assertSame('existing-password', $result->getPassword());
    }

    private function newUser(): User
    {
        $user = new User();
        $user->setUsername('new-user');
        $user->setEmail('new@example.com');
        $user->setFirstName('New');
        $user->setLastName('User');
        $user->setRole(new Role());

        return $user;
    }
}
