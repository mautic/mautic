<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

trait ApiTestUserTrait
{
    /**
     * @param array<string, string[]> $permissions
     */
    private function createApiUserWithPermissions(array $permissions, string $roleNamePrefix = 'Lead API Role'): User
    {
        $role = new Role();
        $role->setName($roleNamePrefix.' '.uniqid('', true));
        $this->em->persist($role);
        $this->em->flush();

        $roleModel = static::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('Lead');
        $user->setLastName('Api');
        $user->setUsername('lead.api.'.uniqid());
        $user->setEmail('lead.api.'.uniqid().'@example.com');
        $user->setRole($role);

        $hasher = static::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function authenticateApiUser(User $user): void
    {
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }
}
