<?php

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;

final class LoadPageHitData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $hits = [
            [
                'ipAddress'  => $this->getReference('ipAddress-1'),
                'url'        => 'http://test.com',
                'urlTitle'   => 'Test Title',
                'referer'    => 'http://mautic.com',
                'alias'      => 'hit-1',
                'contact'    => $this->getReference('lead-1'),
                'dateHit'    => new \DateTime('-1 day'),
                'code'       => 200,
                'trackingId' => 'asdf',
            ],
            [
                'ipAddress'  => $this->getReference('ipAddress-2'),
                'url'        => 'https://test/regex-segment-3.com',
                'urlTitle'   => 'Test Regex Url',
                'referer'    => 'https://test.com',
                'alias'      => 'hit-2',
                'contact'    => $this->getReference('lead-2'),
                'dateHit'    => new \DateTime('-2 day'),
                'code'       => 200,
                'trackingId' => 'abcdr',
            ],
            [
                'ipAddress'  => $this->getReference('ipAddress-3'),
                'url'        => 'https://test/regex-segment-2.com',
                'urlTitle'   => 'Test Regex Url',
                'referer'    => 'https://test.com',
                'alias'      => 'hit-3',
                'contact'    => $this->getReference('lead-3'),
                'dateHit'    => new \DateTime('-3 day'),
                'code'       => 200,
                'trackingId' => 'abcdr',
            ],
            [
                'ipAddress'  => $this->getReference('ipAddress-4'),
                'url'        => 'https://test/regex-segment-85.com',
                'urlTitle'   => 'Test Regex Url',
                'referer'    => 'https://test.com',
                'alias'      => 'hit-4',
                'contact'    => $this->getReference('lead-4'),
                'dateHit'    => new \DateTime('-5 day'),
                'code'       => 200,
                'trackingId' => 'abcdr',
            ],
            [
                'ipAddress'  => $this->getReference('ipAddress-5'),
                'url'        => 'https://test/regex-segment-0.com',
                'urlTitle'   => 'Test Regex Url',
                'referer'    => 'https://test.com',
                'alias'      => 'hit-5',
                'contact'    => $this->getReference('lead-5'),
                'dateHit'    => new \DateTime('-3 day'),
                'code'       => 200,
                'trackingId' => 'abcdr',
            ],
            [
                'ipAddress'  => $this->getReference('ipAddress-5'),
                'url'        => 'https://test/regex-segment-other.com',
                'urlTitle'   => 'Test Title',
                'referer'    => 'https://test.com',
                'alias'      => 'hit-6',
                'contact'    => $this->getReference('lead-5'),
                'dateHit'    => new \DateTime('-3 day'),
                'code'       => 200,
                'trackingId' => 'iomio',
            ],
        ];

        foreach ($hits as $hitConfig) {
            $this->createHit($hitConfig, $manager);
        }
    }

    private function createHit(array $hitConfig, ObjectManager $manager): void
    {
        $hit = new Hit();

        $hit->setIpAddress($hitConfig['ipAddress']);
        $hit->setUrl($hitConfig['url']);
        $hit->setReferer($hitConfig['referer']);
        $hit->setUrlTitle($hitConfig['urlTitle']);
        $hit->setLead($this->getManagedLead($hitConfig['contact'], $manager));
        $hit->setDateHit($hitConfig['dateHit']);
        $hit->setCode($hitConfig['code']);
        $hit->setTrackingId($hitConfig['trackingId']);

        $this->setReference($hitConfig['alias'], $hit);

        $manager->persist($hit);
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
        return 6;
    }
}
