<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Symfony\Component\HttpFoundation\Request;

class RoleControllerFunctionalTest extends MauticMysqlTestCase
{
    private const ROLE_NAME_FIELD = 'role[name]';

    public function testNewRoleAction(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $saveButton = $crawler->selectButton('role[buttons][apply]');

        $name = 'Test Role';
        $desc = 'Role Description';

        $form = $saveButton->form();
        $form[self::ROLE_NAME_FIELD]->setValue($name);
        $form['role[description]']->setValue($desc);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $this->assertStringContainsString($name, $this->client->getResponse()->getContent());
        $this->assertStringContainsString($desc, $this->client->getResponse()->getContent());
    }

    public function testEditRoleAction(): void
    {
        $role = new Role();
        $role->setName('Test Role');
        $role->setDescription('The Description');

        $this->em->persist($role);
        $this->em->flush();

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/edit/'.$role->getId());
        $saveButton = $crawler->selectButton('role[buttons][save]');

        $updatedName = 'Test Role Updated';

        $form = $saveButton->form();
        $form[self::ROLE_NAME_FIELD]->setValue($updatedName);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString($updatedName, $this->client->getResponse()->getContent());
    }

    public function testCloneRoleAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $this->assertTrue($this->client->getResponse()->isOk(), 'Precondition: user can access role creation');

        $role = new Role();
        $role->setName('Original Role');
        $role->setDescription('Original Description');

        $this->em->persist($role);
        $this->em->flush();

        $rolesBefore = $this->em->getRepository(Role::class)->count([]);

        $crawler = $this->client->request(Request::METHOD_POST, '/s/roles/clone/'.$role->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $newName      = 'Cloned Role';
        $saveButton   = $crawler->selectButton('role[buttons][apply]');
        $this->assertGreaterThan(0, $saveButton->count(), 'Expected Apply button on clone form');
        $form         = $saveButton->form();
        $form[self::ROLE_NAME_FIELD]->setValue($newName);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $this->assertStringContainsString($newName, $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Original Description', $this->client->getResponse()->getContent());

        $rolesAfterCount = $this->em->getRepository(Role::class)->count([]);
        $this->assertSame($rolesBefore + 1, $rolesAfterCount);
    }
}
