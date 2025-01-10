<?php

namespace Mautic\UserBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['group_mautic_install_data'];
    }

    public function __construct(
        private RoleModel $roleModel,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        if (!$this->hasReference('admin-role')) {
            $role = new Role();
            $role->setName('Administrators');
            $role->setDescription('Has access to everything.');
            $role->setIsAdmin(1);
            $manager->persist($role);
            $manager->flush();

            $this->addReference('admin-role', $role);
        }

        $role = new Role();
        $role->setName('Sales Team');
        $role->setDescription('Has access to sales');
        $role->setIsAdmin(0);

        $permissions = [
            'user:profile' => ['editname'],
            'lead:leads'   => ['full'],
        ];
        $this->roleModel->setRolePermissions($role, $permissions);

        $manager->persist($role);
        $manager->flush();

        $this->addReference('sales-role', $role);
    }

    public function getOrder()
    {
        return 1;
    }
}
