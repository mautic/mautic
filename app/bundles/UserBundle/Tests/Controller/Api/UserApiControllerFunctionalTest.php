<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testRoleUpdateByApiGivesErrorResponseIfUserDoesNotExist(): void
    {
        $this->client->request(Request::METHOD_PATCH, '/api/users/99999/edit', ['role' => 1]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_NOT_FOUND, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"Item was not found."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseIfRoleDoesNotExist(): void
    {
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', ['role' => 99999]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: This value is not valid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseWithInvalidRequestFormat(): void
    {
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', ['role' => ['id' => 2]]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: This value is not valid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseIfUserDoesNotHaveValidPermissionToUpdate(): void
    {
        // Create non-admin role with non-user edit permissions
        $role = $this->createRole(['lead:leads' => ['viewown']]);
        // Create non-admin user
        $user = $this->createUser($role);
        $this->em->flush();
        $this->em->clear();

        // Login newly created non-admin user
        $this->loginUser($user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

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
        $role = $this->createRole([], true);
        // Create non-admin user
        $user = $this->createUser($role);
        $this->em->flush();
        $this->em->clear();

        // Login newly created admin user
        $this->loginUser($user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", ['role' => $role->getId()]);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"username":"'.$user->getUsername().'"', $clientResponse->getContent());
    }

    /**
     * @param array<string, array<string>> $permission
     */
    private function createRole(array $permission, bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);
        $role->setRawPermissions($permission);
        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('mautic', null));
        $user->setRole($role);
        $this->em->persist($user);

        return $user;
    }
}
