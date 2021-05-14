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
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['group_mautic_install_data'];
    }

    /**
     * @var UserPasswordEncoder
     */
    private $encoder;

    /**
     * {@inheritdoc}
     */
    public function __construct(UserPasswordEncoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setUsername('admin');
        $user->setEmail('admin@yoursite.com');
        $encoder = $this->encoder;
        $user->setPassword($encoder->encodePassword($user, 'mautic'));
        $user->setRole($this->getReference('admin-role'));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('admin-user', $user);

        $user = new User();
        $user->setFirstName('Sales');
        $user->setLastName('User');
        $user->setUsername('sales');
        $user->setEmail('sales@yoursite.com');
        $encoder = $this->encoder;
        $user->setPassword($encoder->encodePassword($user, 'mautic'));
        $user->setRole($this->getReference('sales-role'));
        $manager->persist($user);
        $manager->flush();

        $this->addReference('sales-user', $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
