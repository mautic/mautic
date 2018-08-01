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

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadRoleData.
 */
class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
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
        $this->container->get('mautic.user.model.role')->setRolePermissions($role, $permissions);

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
