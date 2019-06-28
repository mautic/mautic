<?php

namespace Mautic\CoreBundle\DataFixtures\ORM;

use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Entity\Notification;

class NotificationFixtures extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }

    /*
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('admin-user');
        for ($i = 0; $i < 500; ++$i) {
            $n = new Notification();
            $n->setHeader('notification'.$i);
            $n->setMessage('lorem ipsum');
            $n->setDateAdded($this->randomDateInRange(new \DateTime('-8 day'), new DateTime()));
            $n->setUser($user);
            $manager->persist($n);
        }

        $manager->flush();
    }

    private function randomDateInRange(DateTime $start, DateTime $end)
    {
        $randomTimestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
        $randomDate      = new DateTime();
        $randomDate->setTimestamp($randomTimestamp);

        return $randomDate;
    }
}
