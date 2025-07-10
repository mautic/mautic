<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

trait UserEntityTrait
{
    private function loginOtherUser(User $user): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }

    /**
     * @param array<string, mixed> $userDetails
     */
    private function createUserWithPermission(array $userDetails): User
    {
        $role = $this->createRole($userDetails['role']['name']);

        foreach ($userDetails['role']['permissions'] as $permission => $bitwise) {
            $this->createPermission($role, $permission, $bitwise);
        }

        return $this->createUser($userDetails['email'], $userDetails['user-name'], $userDetails['first-name'], $userDetails['last-name'], $role);
    }

    private function createRole(string $name, bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(
        string $email,
        string $username,
        string $firstName,
        string $lastName,
        ?Role $role,
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        if ($role) {
            $user->setRole($role);
        }

        /** @var PasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        $user->setPassword($encoder->hash('Maut1cR0cks!'));

        $this->em->persist($user);

        return $user;
    }

    private function createPermission(Role $role, string $rawPermission, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }
}
