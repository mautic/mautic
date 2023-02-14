<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\Factory;

use Mautic\OpenIdBundle\DTO\UserCredentials;
use Mautic\OpenIdBundle\Exception\EmailRequiredException;
use Mautic\OpenIdBundle\Exception\EmailTakenException;
use Mautic\OpenIdBundle\Exception\RegistrationNotAllowedException;
use Mautic\OpenIdBundle\Exception\RoleNotFoundException;
use Mautic\OpenIdBundle\Factory\UserFactory;
use Mautic\OpenIdBundle\Tests\Builder\DTO\ParametersBuilder;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserFactoryTest extends TestCase
{
    public function testBuild(): void
    {
        $role       = self::createMock(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRole($role)->build();
        $userRepo   = self::createMock(UserRepository::class);
        $roleRepo   = self::createMock(RoleRepository::class);
        $logger     = self::createMock(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        $credentials = new UserCredentials('id', 'email@mautic.local', '', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $user        = $userFactory->create($credentials);

        self::assertEquals($credentials->getEmail(), $user->getEmail());
        self::assertEquals($credentials->getGivenName(), $user->getFirstName());
        self::assertEquals($credentials->getFamilyName(), $user->getLastName());
        self::assertEquals($credentials->getEmail(), $user->getUsername());
        self::assertTrue($user->getIsPublished());
    }

    public function testBuildThrowsExceptionWhenRoleNotFound(): void
    {
        $parameters = (new ParametersBuilder())->build();
        $userRepo   = self::createMock(UserRepository::class);
        $roleRepo   = self::createMock(RoleRepository::class);
        $logger     = self::createMock(LoggerInterface::class);

        self::expectException(RoleNotFoundException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenEmailNotFound(): void
    {
        $role       = self::createMock(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRole($role)->build();
        $userRepo   = self::createMock(UserRepository::class);
        $roleRepo   = self::createMock(RoleRepository::class);
        $logger     = self::createMock(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        self::expectException(EmailRequiredException::class);

        $credentials = new UserCredentials('id', null, 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenEmailIsTaken(): void
    {
        $role       = self::createMock(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRole($role)->build();
        $userRepo   = self::createMock(UserRepository::class);
        $roleRepo   = self::createMock(RoleRepository::class);
        $logger     = self::createMock(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        $userRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'email@mautic.local'])
            ->willReturn(new User());

        self::expectException(EmailTakenException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenRegistrationIsDisabled(): void
    {
        $parameters = (new ParametersBuilder())->withIsUserRegistrationAllowed(false)->build();
        $userRepo   = self::createMock(UserRepository::class);
        $roleRepo   = self::createMock(RoleRepository::class);
        $logger     = self::createMock(LoggerInterface::class);

        self::expectException(RegistrationNotAllowedException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }
}
