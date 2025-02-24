<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private TranslatorInterface $translator
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

        $role = new Role();
        $role->setName($this->translator->trans('mautic.user.role.admin.name', [], 'fixtures'));
        $role->setDescription($this->translator->trans('mautic.user.role.admin.description', [], 'fixtures'));
        $role->setIsAdmin(1);
        $manager->persist($role);
        $manager->flush();

        $this->addReference('admin-role', $role);
    }

    public function getOrder()
    {
        return 1;
    }
}
