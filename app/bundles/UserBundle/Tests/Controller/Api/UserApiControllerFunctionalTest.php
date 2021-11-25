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
        $parameters = [
            'role' => 1,
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/users/99999/edit', $parameters);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_NOT_FOUND, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('Item was not found.', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseIfRoleDoesNotExist(): void
    {
        $parameters = [
            'role' => 99999,
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', $parameters);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: This value is not valid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesErrorResponseWithInvalidRequestFormat(): void
    {
        $parameters = [
            'role' => [
                'id' => 2,
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/users/1/edit', $parameters);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString('"message":"role: This value is not valid."', $clientResponse->getContent());
    }

    public function testRoleUpdateByApiGivesSuccessResponse(): void
    {
        // Create non-admin role with non-api permissions
        $role = $this->createRole(['user:users' => ['edit']]);
        // Create non-admin user
        $user = $this->createAdmin($role);
        $this->em->flush();
        $this->em->clear();

        $parameters = ['role' => $role->getId()];
        $this->loginUser($user->getUsername());
        $this->client->request(Request::METHOD_PATCH, "/api/users/{$user->getId()}/edit", $parameters);
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

    private function createAdmin(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $user->setPassword('Ax417Rl$v&');
        $user->setRole($role);
        $this->em->persist($user);

        return $user;
    }
}
