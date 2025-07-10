<?php

namespace MauticPlugin\MauticSocialBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class MonitoringControllerTest extends MauticMysqlTestCase
{
    public const USERNAME = 'jhony';

    public function testIndex(): void
    {
        $this->client->request('GET', '/s/monitoring');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNew(): void
    {
        $this->client->request('GET', '/s/monitoring/new');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEdit(): void
    {
        $this->client->request('GET', '/s/monitoring/edit/1');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testIndexWithoutPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/monitoring');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testNewWithoutPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/monitoring/new');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testEditWithoutPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/monitoring/edit/1');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

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

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
