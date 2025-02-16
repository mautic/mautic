<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class RoleControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testNewRoleAction(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $saveButton = $crawler->selectButton('role[buttons][apply]');

        $name = 'Test Role';
        $desc = 'Role Description';

        $form = $saveButton->form();
        $form['role[name]']->setValue($name);
        $form['role[description]']->setValue($desc);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $this->assertStringContainsString($name, $this->client->getResponse()->getContent());
        $this->assertStringContainsString($desc, $this->client->getResponse()->getContent());
    }

    public function testEditRoleAction(): void
    {
        $role       = $this->createRole('Test Role');
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/edit/'.$role->getId());
        $saveButton = $crawler->selectButton('role[buttons][save]');

        $updatedName = 'Test Role Updated';

        $form = $saveButton->form();
        $form['role[name]']->setValue($updatedName);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString($updatedName, $this->client->getResponse()->getContent());
    }

    public function testBatchDeleteRoleAction(): void
    {
        $usedRole   = $this->createRole('Used Role');
        $unusedRole = $this->createRole('Unused Role');
        $user       = $this->createUser($usedRole);

        $this->client->request(Request::METHOD_POST, '/s/roles/batchDelete?ids=all');
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $this->assertSame($user->getRole(), $usedRole);
        $this->assertStringContainsString($usedRole->getName().' cannot be deleted because it still has users assigned to it', $response->getContent());
        $this->assertNull($unusedRole->getId());
    }

    private function createRole(string $title): Role
    {
        $role = new Role();
        $role->setName($title);
        $role->setDescription('The Description');

        $this->em->persist($role);
        $this->em->flush();

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@email.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('password');
        $user->setRole($role);
        $user->setLastLogin('2024-02-22 10:30:00');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
