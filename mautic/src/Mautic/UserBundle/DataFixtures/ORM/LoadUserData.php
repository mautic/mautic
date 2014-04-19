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
use Mautic\UserBundle\Entity\User;

/**
 * Class LoadUserData
 *
 * @package Mautic\UserBundle\DataFixtures\ORM
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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

        $user = new User();
        $user->setFirstName($translator->trans('mautic.user.user.admin.name', array(), 'fixtures'));
        $user->setLastName($translator->trans('mautic.user.user.admin.name', array(), 'fixtures'));
        $user->setUsername('admin');
        $user->setEmail('admin@yoursite.com');
        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($user)
        ;
        $user->setPassword($encoder->encodePassword('mautic', $user->getSalt()));
        $user->setRole($this->getReference('admin-role'));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('admin-user', $user);

        $user = new User();
        $user->setFirstName($translator->trans('mautic.user.user.sales.name', array(), 'fixtures'));
        $user->setLastName($translator->trans('mautic.user.user.sales.name', array(), 'fixtures'));
        $user->setUsername('sales');
        $user->setEmail('sales@yoursite.com');
        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($user)
        ;
        $user->setPassword($encoder->encodePassword('mautic', $user->getSalt()));
        $user->setRole($this->getReference('sales-role'));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('sales-user', $user);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 2;
    }
}