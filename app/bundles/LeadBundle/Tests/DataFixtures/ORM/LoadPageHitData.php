<?php

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\PageBundle\Entity\Hit;

class LoadPageHitData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
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

    protected function createHit($hitConfig, ObjectManager $manager)
    {
        $hit = new Hit();

        $hit->setIpAddress($hitConfig['ipAddress']);
        $hit->setUrl($hitConfig['url']);
        $hit->setReferer($hitConfig['referer']);
        $hit->setUrlTitle($hitConfig['urlTitle']);
        $hit->setLead($hitConfig['contact']);
        $hit->setDateHit($hitConfig['dateHit']);
        $hit->setCode($hitConfig['code']);
        $hit->setTrackingId($hitConfig['trackingId']);

        $this->setReference($hitConfig['alias'], $hit);

        $manager->persist($hit);
        $manager->flush();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 6;
    }
}
