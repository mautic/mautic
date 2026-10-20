<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoleData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private RoleRepository $roleRepository,
    ) {
    }

    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    public function load(ObjectManager $manager): void
    {
        if ($this->hasReference('admin-role')) {
            return;
        }

        $roleName = $this->translator->trans('mautic.user.role.admin.name', [], 'fixtures');

        // Prevent unique constraint violation when the role already exists in the database
        $role = $manager->roleRepository->findOneBy(['name' => $roleName]);

        if (!$role) {
            $role = new Role();
            $role->setName($roleName);
            $role->setDescription($this->translator->trans('mautic.user.role.admin.description', [], 'fixtures'));
            $role->setIsAdmin(1);
            $manager->persist($role);
            $manager->flush();
        }

        $this->addReference('admin-role', $role);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
