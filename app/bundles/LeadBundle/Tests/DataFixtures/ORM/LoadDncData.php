<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\DoNotContact;

final class LoadDncData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $dnc = new DoNotContact();
        $dnc->setChannel('sms');
        $dnc->setReason(DoNotContact::MANUAL);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setLead($this->getReference('lead-1'));

        $manager->persist($dnc);
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 8;
    }
}
