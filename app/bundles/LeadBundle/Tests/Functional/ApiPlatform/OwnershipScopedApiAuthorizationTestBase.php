<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\ApiPlatform;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[Group('non-parallel')]
abstract class OwnershipScopedApiAuthorizationTestBase extends MauticMysqlTestCase
{
    /**
     * @param array<string, array<string>> $permissions
     */
    protected function createUserWithPermissions(string $username, string $email, string $password, array $permissions): User
    {
        $role = new Role();
        $role->setName('role_'.$username);
        $role->setIsAdmin(false);

        $roleModel = $this->getService(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRole($role);

        $hasher = $this->getService(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $user->setPassword($hasher->hash($password));

        $this->em->persist($role);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
