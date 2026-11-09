<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class ElFinderControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_METHOD'] ??= 'GET';
    }

    #[DataProvider('dataUrls')]
    public function testElfinderWithAdminUser(string $url): void
    {
        $this->client->request('GET', $url);
        $this->assertResponseIsSuccessful('Admin user should have sufficient permissions.');
    }

    #[DataProvider('dataUrls')]
    public function testElfinderWithSufficientPermissionsForNonAdmin(string $url): void
    {
        $user = $this->createUserWithPermissions(['core:file_manager' => ['full']]);
        $this->loginUser($user);

        $this->client->request('GET', $url);
        $this->assertResponseIsSuccessful('A non-admin user with file manager permission should have access.');
    }

    #[DataProvider('dataUrls')]
    public function testElfinderWithInsufficientPermissionsForNonAdmin(string $url): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->assertInstanceOf(User::class, $user);
        $this->loginUser($user);

        $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(403, 'The status forbidden should be returned for users without permissions.');
        $this->assertStringContainsString('Forbidden', (string) $response->getContent());
    }

    public static function dataUrls(): iterable
    {
        foreach (['/elfinder', '/efconnect', '/elfinder.main.js'] as $url) {
            yield $url => [$url];
        }
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $roleModel = self::getContainer()->get(RoleModel::class);
        $this->assertInstanceOf(RoleModel::class, $roleModel);

        $hasherFactory = self::getContainer()->get(PasswordHasherFactoryInterface::class);
        $this->assertInstanceOf(PasswordHasherFactoryInterface::class, $hasherFactory);

        $role = new Role();
        $role->setName('Test role');
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setUsername('testUser');
        $user->setEmail('testuser@example.com');
        $user->setRole($role);
        $user->setPassword($hasherFactory->getPasswordHasher($user)->hash('Maut1cR0cks!'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
