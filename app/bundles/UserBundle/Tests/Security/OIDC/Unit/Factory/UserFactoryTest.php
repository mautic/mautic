<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Factory;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Exception\OidcException;
use Mautic\UserBundle\Security\OIDC\DTO\UserCredentials;
use Mautic\UserBundle\Security\OIDC\User\UserFactory;
use Mautic\UserBundle\Tests\Security\OIDC\Builder\DTO\ParametersBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserFactoryTest extends TestCase
{
    public function testBuild(): void
    {
        $role       = $this->createStub(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRoleId(1)->build();
        $userRepo   = $this->createStub(UserRepository::class);
        $roleRepo   = $this->createMock(RoleRepository::class);
        $logger     = $this->createStub(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        // preferredUsername collides → falls back to email as username
        $userRepo->method('findByIdentifier')
            ->willReturnCallback(static function (string $identifier): ?User {
                return 'preferredUsername' === $identifier ? new User() : null;
            });

        $credentials = new UserCredentials('id', 'email@mautic.local', '', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $user        = $userFactory->create($credentials);

        $this->assertEquals($credentials->getEmail(), $user->getEmail());
        $this->assertEquals($credentials->getGivenName(), $user->getFirstName());
        $this->assertEquals($credentials->getFamilyName(), $user->getLastName());
        $this->assertEquals($credentials->getEmail(), $user->getUsername());
        $this->assertTrue($user->getIsPublished());
    }

    public function testBuildThrowsExceptionWhenRoleNotFound(): void
    {
        $parameters = (new ParametersBuilder())->build();
        $userRepo   = $this->createStub(UserRepository::class);
        $roleRepo   = $this->createStub(RoleRepository::class);
        $logger     = $this->createStub(LoggerInterface::class);

        $this->expectException(OidcException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenEmailNotFound(): void
    {
        $role       = $this->createStub(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRoleId(1)->build();
        $userRepo   = $this->createStub(UserRepository::class);
        $roleRepo   = $this->createMock(RoleRepository::class);
        $logger     = $this->createStub(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        $this->expectException(OidcException::class);

        $credentials = new UserCredentials('id', null, 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenEmailIsTaken(): void
    {
        $role       = $this->createStub(Role::class);
        $parameters = (new ParametersBuilder())->withRegisteredUserRoleId(1)->build();
        $userRepo   = $this->createMock(UserRepository::class);
        $roleRepo   = $this->createMock(RoleRepository::class);
        $logger     = $this->createStub(LoggerInterface::class);

        $roleRepo->method('find')
            ->willReturn($role);

        // Production code uses findByIdentifier() (case-insensitive), not findOneBy().
        $userRepo->expects($this->once())
            ->method('findByIdentifier')
            ->with('email@mautic.local')
            ->willReturn(new User());

        $this->expectException(OidcException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }

    public function testBuildThrowsExceptionWhenRegistrationIsDisabled(): void
    {
        $parameters = (new ParametersBuilder())->withIsUserRegistrationAllowed(false)->build();
        $userRepo   = $this->createStub(UserRepository::class);
        $roleRepo   = $this->createStub(RoleRepository::class);
        $logger     = $this->createStub(LoggerInterface::class);

        $this->expectException(OidcException::class);

        $credentials = new UserCredentials('id', 'email@mautic.local', 'givenName', 'familyName', 'preferredUsername');
        $userFactory = new UserFactory($parameters, $userRepo, $roleRepo, $logger);
        $userFactory->create($credentials);
    }
}
