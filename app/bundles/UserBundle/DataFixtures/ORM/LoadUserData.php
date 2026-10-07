<?php

namespace Mautic\UserBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['group_mautic_install_data'];
    }

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $adminRole = $this->getReference('admin-role');
        \assert($adminRole instanceof Role);
        \assert($manager instanceof EntityManagerInterface);

        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername('admin');
        $user->setEmail('admin@yoursite.com');
        $user->setPassword($this->hasher->hashPassword($user, 'Maut1cR0cks!'));
        $user->setRole($manager->getReference(Role::class, $adminRole->getId()));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('admin-user', $user);

        $salesRole = $this->getReference('sales-role');
        \assert($salesRole instanceof Role);

        $user = new User();
        $user->setFirstName('Sales');
        $user->setLastName('User');
        $user->setUsername('sales');
        $user->setEmail('sales@yoursite.com');
        $user->setPassword($this->hasher->hashPassword($user, 'Maut1cR0cks!'));
        $user->setRole($manager->getReference(Role::class, $salesRole->getId()));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('sales-user', $user);
    }

    public function getOrder(): int
    {
        return 2;
    }
}
