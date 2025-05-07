<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
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
        $role = new Role();
        $role->setName('Test Role');
        $role->setDescription('The Description');

        $this->em->persist($role);
        $this->em->flush();

        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/edit/'.$role->getId());
        $saveButton = $crawler->selectButton('role[buttons][save]');

        $updatedName = 'Test Role Updated';

        $form = $saveButton->form();
        $form['role[name]']->setValue($updatedName);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->assertStringContainsString($updatedName, $this->client->getResponse()->getContent());
    }
}
