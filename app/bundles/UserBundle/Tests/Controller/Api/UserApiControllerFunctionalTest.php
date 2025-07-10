<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testRoleUpdateByApiGivesErrorResponseIfUserDoesNotExist(): void
    {
        // Assuming user with id 99999 does not exist
        $this->client->request(Request::METHOD_PATCH, '/api/users/99999/edit', ['role' => 1]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_NOT_FOUND, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"Item was not found."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseIfRoleDoesNotExist(): void
    {
        // Assuming role with id 99999 does not exist
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', ['role' => 99999]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: The selected choice is invalid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseWithInvalidRequestFormat(): void
    {
        // Correct request format is ['role' => 2]
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', ['role' => ['id' => 2]]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: The selected choice is invalid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseIfUserDoesNotHaveValidPermissionToUpdate(): void
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create permissions for the role
        $this->createPermission('lead:leads:viewown', $role, 1024);
        // Create non-admin user
        $user = $this->createUser($role);
        $this->em->flush();
        $this->em->clear();

        // Login newly created non-admin user
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", ['role' => $role->getId()]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_FORBIDDEN, $clientResponse->getStatusCode());
        Assert::assertStringContainsString(
            '"message":"You do not have access to the requested area\/action."',
            $clientResponse->getContent()
        );
    }

    public function testRoleUpdateByApiThroughAdminUserGivesSuccessResponse(): void
    {
        // Create admin role
        $role = $this->createRole(true);
        // Create admin user
        $user = $this->createUser($role);
        $this->em->flush();
        $this->em->clear();

        // Login newly created admin user
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", ['role' => $role->getId()]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"username":"'.$user->getUserIdentifier().'"', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiThroughNonAdminUserGivesSuccessResponse(): void
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create permissions to update user for the role
        $this->createPermission('user:users:edit', $role, 52);
        // Create non-admin user
        $user = $this->createUser($role);
        $this->em->flush();
        $this->em->clear();

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", ['role' => $role->getId()]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"username":"'.$user->getUserIdentifier().'"', $clientResponse->getContent());
    }

    public function testWeakPasswordGivesUnauthorizedResponse(): void
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create permissions to update user for the role
        $this->createPermission('user:users:edit', $role, 52);
        // Create non-admin user with weak password.
        $weakPassword = 'mautic';
        $user         = $this->createUser($role, $weakPassword);
        $this->em->flush();
        $this->em->clear();

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', $weakPassword);

        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", ['role' => $role->getId()]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_UNAUTHORIZED, $clientResponse->getStatusCode());
    }

    /**
     * @dataProvider passwordProvider
     */
    public function testUserPasswordPolicy(int $responseCode, string $password): void
    {
        $userPayload = [
            'username'      => 'lorem_ipsum',
            'firstName'     => 'lorem',
            'lastName'      => 'ipsum',
            'email'         => 'loremipsum@example.com',
            'plainPassword' => ['password' => $password, 'confirm' => $password],
            'role'          => 1,
        ];

        $this->client->request(Request::METHOD_POST, '/api/users/new', $userPayload);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame($responseCode, $clientResponse->getStatusCode());
    }

    /**
     * @return iterable<array<int, mixed>>
     */
    public function passwordProvider(): iterable
    {
        yield [Response::HTTP_BAD_REQUEST, 'aaa'];
        yield [Response::HTTP_BAD_REQUEST, 'qwerty'];
        yield [Response::HTTP_BAD_REQUEST, 'qwerty123'];
        yield [Response::HTTP_CREATED, 'Qwertee@123'];
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);
        $this->em->persist($role);

        return $role;
    }

    private function createPermission(string $rawPermission, Role $role, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private function createUser(Role $role, string $password = 'Maut1cR0cks!'): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash($password));
        $user->setRole($role);
        $this->em->persist($user);

        return $user;
    }
}
