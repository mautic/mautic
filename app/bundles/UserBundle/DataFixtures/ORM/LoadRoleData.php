<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Model\RoleModel;

class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['group_mautic_install_data'];
    }

    /**
     * @var RoleModel
     */
    private $roleModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(RoleModel $roleModel)
    {
        $this->roleModel = $roleModel;
    }

    public function load(ObjectManager $manager)
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
