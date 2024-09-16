<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicContentControllerFunctionalTest extends MauticMysqlTestCase
{
    const PERMISSION_CREATE       = 'dynamiccontent:dynamiccontents:create';
    const PERMISSION_DELETE_OTHER = 'dynamiccontent:dynamiccontents:deleteother';
    const PERMISSION_DELETE_OWN   = 'dynamiccontent:dynamiccontents:deleteown';

    const BITWISE_BY_PERM = [
        self::PERMISSION_CREATE       => 52,
        self::PERMISSION_DELETE_OWN   => 66,
        self::PERMISSION_DELETE_OTHER => 150,
    ];

    public function testAccessControlNewAction(): void
    {
        $this->createAndLoginUser(self::PERMISSION_CREATE);
        $this->client->request(Request::METHOD_GET, '/s/dwc/new');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testForbiddenNewAction(): void
    {
        $this->createAndLoginUser();
        $this->client->request(Request::METHOD_GET, '/s/dwc/new');

        Assert::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAccessDeleteAction(): void
    {
        $this->createAndLoginUser(self::PERMISSION_DELETE_OWN);
        $this->client->request(Request::METHOD_POST, '/s/dwc/delete');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testForbiddenDeleteAction(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/dwc/delete');

        Assert::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    private function createAndLoginUser(string $permission = null): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create permissions to update user for the role
        if (!empty($permission)) {
            $this->createPermission($permission, $role, self::BITWISE_BY_PERM[$permission]);
        }
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->clear();

        $this->loginUser($user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');

        return $user;
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

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername('john.doe');
        $user->setEmail('john.doe@email.com');
        $encoder = self::$container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('mautic', null));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
