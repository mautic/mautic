<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;

final class LoadDncData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $dnc = new DoNotContact();
        $dnc->setChannel('sms');
        $dnc->setReason(DoNotContact::MANUAL);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setLead($this->getManagedLead($this->getReference('lead-1'), $manager));

        $manager->persist($dnc);
        $manager->flush();
    }

    private function getManagedLead(Lead $lead, ObjectManager $manager): Lead
    {
        \assert($manager instanceof EntityManagerInterface);
        $managedLead = $manager->getReference(Lead::class, $lead->getId());
        \assert($managedLead instanceof Lead);

        return $managedLead;
    }

    public function getOrder(): int
    {
        return 8;
    }
}
