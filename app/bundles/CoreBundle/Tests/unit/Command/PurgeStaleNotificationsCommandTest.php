<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Command\PurgeStaleNotificationsCommand;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PurgeStaleNotificationsCommandTest extends MauticMysqlTestCase
{
    /**
     * @test
     */
    public function it_purges_notifcations_older_than_seven_days_old_by_default()
    {
        $staleNotifications      = [];
        $staleCount              = 10;
        for ($i = 0; $i < $staleCount; ++$i) {
            $date = $this->randomDateInRange(new \DateTime('-30 days'), new \DateTime('-7 days'));
            $not  = $this->createNotification($date);
            $this->em->persist($not);
            $staleNotifications[] = $not;
        }

        $freshNotifications      = [];
        $freshCount              = 15;
        for ($i = 0; $i < $freshCount; ++$i) {
            $date = $this->randomDateInRange(new \DateTime('-6 days'), new \DateTime());
            $not  = $this->createNotification($date);
            $this->em->persist($not);
            $freshNotifications[] = $not;
        }
        $this->em->flush();

        $this->assertEquals(($staleCount + $freshCount), $this->getNotificationCount());

        $command     = $this->getCommand();
        $commandTest = new CommandTester($command);
        $commandTest->execute(['command' => $command->getName()]);
        $output = $commandTest->getDisplay();

        $this->assertContains('Done', $output);
        $this->assertEquals($freshCount, $this->getNotificationCount());
    }

    /**
     * Helper method to make creating Notifications easier.
     *
     * @param \DateTime $dateAdded
     * @param string    $message
     */
    private function createNotification($dateAdded, $message = 'Test Message')
    {
        $notification = new Notification();
        $notification->setMessage($message);
        $notification->setDateAdded($dateAdded);
        $notification->setUser($this->createUser());

        return $notification;
    }

    /**
     * Return the count of current notifications.
     *
     * @return int
     */
    private function getNotificationCount()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(n.id)');
        $qb->from(Notification::class, 'n');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get the PurgeStaleNotificationsCommand command.
     *
     * @return PurgeStaleNotificationsCommand
     */
    private function getCommand()
    {
        $app     = new Application(static::$kernel);

        return $app->find('mautic:notifications:purge');
    }

    /**
     * Get a random \DateTime between $start and $end.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return \DateTime
     */
    private function randomDateInRange(\DateTime $start, \DateTime $end)
    {
        $randomTimestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
        $randomDate      = new \DateTime();
        $randomDate->setTimestamp($randomTimestamp);

        return $randomDate;
    }

    /**
     * Get or create the admin user.
     *
     * @return User
     */
    private function createUser()
    {
        // NOTE: This codebase really needs some sort of 'factory' for entities,
        // this makes it difficult and repetitive to test functionality.
        /** @var UserRepository $repo */
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['username' => 'admin']);
        if (null === $user) {
            $user = new User();
            $user->setFirstName('Admin');
            $user->setLastName('User');
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
        }

        return $user;
    }
}
