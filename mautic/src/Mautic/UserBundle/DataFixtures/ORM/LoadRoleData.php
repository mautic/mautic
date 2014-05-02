<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\UserBundle\Entity\Role;

/**
 * Class LoadRoleData
 *
 * @package Mautic\UserBundle\DataFixtures\ORM
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
        $translator = $this->container->get('translator');
        $role = new Role();
        $role->setName($translator->trans('mautic.user.role.admin.name', array(), 'fixtures'));
        $role->setDescription($translator->trans('mautic.user.role.admin.description', array(), 'fixtures'));
        $role->setIsAdmin(1);
        $manager->persist($role);
        $manager->flush();

        $this->addReference('admin-role', $role);

        $role = new Role();
        $role->setName($translator->trans('mautic.user.role.sales.name', array(), 'fixtures'));
        $role->setDescription($translator->trans('mautic.user.role.sales.description', array(), 'fixtures'));
        $role->setIsAdmin(0);

        $permissions = array(
            'api:access'   => array('prohibit'),
            'user:profile' => array('editname'),
            'lead:leads'   => array('full')
        );
        $this->container->get('mautic.model.role')->setRolePermissions($role, $permissions);

        $manager->persist($role);
        $manager->flush();

        $this->addReference('sales-role', $role);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}