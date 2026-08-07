<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RoleControllerFunctionalTest extends MauticMysqlTestCase
{
    private const string ROLE_NAME_FIELD        = 'role[name]';

    private const string ROLE_DESCRIPTION_FIELD = 'role[description]';

    public function testNewRoleAction(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $saveButton = $crawler->selectButton('role[buttons][apply]');

        $name = 'Test Role';
        $desc = 'Role Description';

        $form = $saveButton->form();
        $form[self::ROLE_NAME_FIELD]->setValue($name);
        $form[self::ROLE_DESCRIPTION_FIELD]->setValue($desc);

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString($name, (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString($desc, (string) $this->client->getResponse()->getContent());
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
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString($updatedName, (string) $this->client->getResponse()->getContent());
    }

    public function testCloneRoleAction(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/roles/new');
        $this->assertResponseIsSuccessful('Precondition: user can access role creation');

        $role = new Role();
        $role->setName('Original Role');
        $role->setDescription('Original Description');
        $role->setRawPermissions([
            'stale' => [],
        ]);

        $permission = new Permission();
        $permission->setBundle('user');
        $permission->setName('roles');
        $permission->setRole($role);
        $permission->setBitwise(20);

        $this->em->persist($role);
        $this->em->persist($permission);
        $this->em->flush();

        $rolesBefore = $this->em->getRepository(Role::class)->count([]);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles/clone/'.$role->getId());
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $newName      = 'Cloned Role';
        $saveButton   = $crawler->selectButton('role[buttons][apply]');
        $this->assertGreaterThan(0, $saveButton->count(), 'Expected Apply button on clone form');
        $form         = $saveButton->form();
        $form[self::ROLE_NAME_FIELD]->setValue($newName);
        $this->client->submit($form);
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $this->assertStringContainsString($newName, (string) $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Original Description', (string) $this->client->getResponse()->getContent());

        $rolesAfterCount = $this->em->getRepository(Role::class)->count([]);
        $this->assertSame($rolesBefore + 1, $rolesAfterCount);

        $clonedRole = $this->em->getRepository(Role::class)->findOneBy(['name' => $newName]);
        $this->assertInstanceOf(Role::class, $clonedRole);
        $this->assertSame(['view', 'edit'], $clonedRole->getRawPermissions()['user:roles']);
    }

    public function testCloneRoleActionRendersInvalidFormAgain(): void
    {
        $role = new Role();
        $role->setName('Role with invalid clone');
        $this->em->persist($role);
        $this->em->flush();

        $rolesBefore = $this->em->getRepository(Role::class)->count([]);
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/roles/clone/'.$role->getId());
        $form        = $crawler->selectButton('role[buttons][apply]')->form();
        $form[self::ROLE_NAME_FIELD]->setValue('');

        $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSame($rolesBefore, $this->em->getRepository(Role::class)->count([]));
        $this->assertGreaterThan(0, $this->client->getCrawler()->selectButton('role[buttons][apply]')->count());
    }

    public function testCloneRoleActionReturnsToTheListWhenCancelled(): void
    {
        $role = new Role();
        $role->setName('Role not cloned');
        $this->em->persist($role);
        $this->em->flush();

        $rolesBefore = $this->em->getRepository(Role::class)->count([]);
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/roles/clone/'.$role->getId());
        $cancelButton = $crawler->selectButton('role[buttons][cancel]');

        $this->client->submit($cancelButton->form());

        $this->assertResponseIsSuccessful();
        $this->assertSame($rolesBefore, $this->em->getRepository(Role::class)->count([]));
    }

    public function testCloneRoleActionReportsMissingRole(): void
    {
        $missingRoleId = 999999;

        $this->client->request(Request::METHOD_GET, '/s/roles/clone/'.$missingRoleId);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            sprintf('Role not found with an ID of <strong>%d</strong>.', $missingRoleId),
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testCloneRoleActionRequiresCreatePermission(): void
    {
        $role = $this->loginRoleViewer('clone_role_viewer');
        $this->client->request(Request::METHOD_GET, '/s/roles/clone/'.$role->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteRoleActionRequiresDeletePermission(): void
    {
        $role = $this->loginRoleViewer('delete_role_viewer');

        $this->client->request(Request::METHOD_GET, '/s/roles/delete/'.$role->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testBatchDeleteRoleActionChecksDeletePermission(): void
    {
        $this->loginRoleViewer('batch_delete_role_viewer');

        $targetRole = new Role();
        $targetRole->setName('Role not batch deleted');
        $this->em->persist($targetRole);
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            '/s/roles/batchDelete/0?ids='.rawurlencode((string) json_encode([$targetRole->getId()]))
        );

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(Role::class, $this->em->getRepository(Role::class)->find($targetRole->getId()));
    }

    public function testIndexActionCanSortByUserCount(): void
    {
        $uniquePrefix = 'TestRole'.uniqid();

        $role1 = new Role();
        $role1->setName($uniquePrefix.' 1');
        $this->em->persist($role1);

        $role2 = new Role();
        $role2->setName($uniquePrefix.' 2');
        $this->em->persist($role2);

        $role3 = new Role();
        $role3->setName($uniquePrefix.' 3');
        $this->em->persist($role3);

        $this->em->flush();

        $this->em->persist($this->createUser('user1', $role1));
        $this->em->persist($this->createUser('user2', $role1));
        $this->em->persist($this->createUser('user3', $role2));
        $this->em->flush();

<<<<<<< HEAD
<<<<<<< HEAD
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=DESC');
=======
        $crawler = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=DESC');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=DESC');
>>>>>>> 222589fde5 (cs)
        $rows    = $crawler->filter('#roleTable tbody tr');

        $this->assertSame($uniquePrefix.' 1', trim($rows->eq(0)->filter('td')->eq(1)->text()));
        $this->assertSame($uniquePrefix.' 2', trim($rows->eq(1)->filter('td')->eq(1)->text()));
        $this->assertSame($uniquePrefix.' 3', trim($rows->eq(2)->filter('td')->eq(1)->text()));

<<<<<<< HEAD
<<<<<<< HEAD
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=ASC');
=======
        $crawler = $this->client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=ASC');
>>>>>>> a7c9fd10b7 ([probe] [symfony] use symfony code-quality set)
=======
        $crawler = $this->client->request(Request::METHOD_GET, '/s/roles?tmpl=list&search='.$uniquePrefix.'&orderby=user_count&orderbydir=ASC');
>>>>>>> 222589fde5 (cs)
        $rows    = $crawler->filter('#roleTable tbody tr');

        $this->assertSame($uniquePrefix.' 3', trim($rows->eq(0)->filter('td')->eq(1)->text()));
        $this->assertSame($uniquePrefix.' 2', trim($rows->eq(1)->filter('td')->eq(1)->text()));
        $this->assertSame($uniquePrefix.' 1', trim($rows->eq(2)->filter('td')->eq(1)->text()));
    }

    private function createUser(string $username, Role $role): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@example.com');
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->setPassword('password');
        $user->setRole($role);

        return $user;
    }

    private function loginRoleViewer(string $username): Role
    {
        $role = new Role();
        $role->setName('Role viewer '.$username);

        $permission = new Permission();
        $permission->setBundle('user');
        $permission->setName('roles');
        $permission->setRole($role);
        $permission->setBitwise(4);

        $user = $this->createUser($username, $role);
        $this->em->persist($role);
        $this->em->persist($permission);
        $this->em->persist($user);
        $this->em->flush();

        $this->loginUser($user);

        return $role;
    }
}
