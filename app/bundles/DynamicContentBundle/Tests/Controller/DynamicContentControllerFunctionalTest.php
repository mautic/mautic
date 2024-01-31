<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicContentControllerFunctionalTest extends MauticMysqlTestCase
{
    public const PERMISSION_CREATE       = 'dynamiccontent:dynamiccontents:create';

    public const PERMISSION_DELETE_OTHER = 'dynamiccontent:dynamiccontents:deleteother';

    public const PERMISSION_DELETE_OWN   = 'dynamiccontent:dynamiccontents:deleteown';

    public const BITWISE_BY_PERM = [
        self::PERMISSION_CREATE       => 52,
        self::PERMISSION_DELETE_OWN   => 66,
        self::PERMISSION_DELETE_OTHER => 150,
    ];

    public function testAccessControlNewAction(): void
    {
        $this->createAndLoginUser(self::PERMISSION_CREATE);
        $this->client->request(Request::METHOD_GET, '/s/dwc/new');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testForbiddenNewAction(): void
    {
        $this->createAndLoginUser();
        $this->client->request(Request::METHOD_GET, '/s/dwc/new');

        Assert::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testAccessDeleteAction(): void
    {
        $this->createAndLoginUser(self::PERMISSION_DELETE_OWN);
        $this->client->request(Request::METHOD_POST, '/s/dwc/delete');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    public function testForbiddenDeleteAction(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/dwc/delete');

        Assert::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
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
        $this->em->detach($role);

        $this->loginUser($user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
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

    public function testIndexActionIsSuccessful(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/dwc');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNewActionIsSuccessful(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/dwc/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testEditActionIsSuccessful(): void
    {
        $entity = new DynamicContent();
        $entity->setName('Test Dynamic Content');
        $this->em->persist($entity);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/dwc/edit/'.$entity->getId());
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testViewActionIsSuccessful(): void
    {
        $entity = new DynamicContent();
        $entity->setName('Test Dynamic Content');
        $this->em->persist($entity);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/dwc/view/'.$entity->getId());
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}
