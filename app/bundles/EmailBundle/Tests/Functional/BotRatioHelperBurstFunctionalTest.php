<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Tests\Functional\Fixtures\EmailFixturesHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\HttpFoundation\Request;

final class BotRatioHelperBurstFunctionalTest extends MauticMysqlTestCase
{
    private EmailFixturesHelper $emailFixturesHelper;

    protected function setUp(): void
    {
        $this->configParams['bot_helper_burst_max_distinct_ips'] = 3;
        $this->configParams['bot_helper_burst_window_seconds']   = 300;
        parent::setUp();

        $this->emailFixturesHelper = new EmailFixturesHelper($this->em);
    }

    public function testOpenIsIgnoredAfterDistinctIpBurst(): void
    {
        $stat = $this->createStatWithBurstHits(3);

        $this->client->request(
            Request::METHOD_GET,
            '/email/'.$stat->getTrackingHash().'.gif',
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REMOTE_ADDR'     => '203.0.113.40',
            ]
        );

        $this->assertResponseIsSuccessful();

        $updatedStat = $this->em->getRepository(Stat::class)->find($stat->getId());
        $this->assertInstanceOf(Stat::class, $updatedStat);
        $this->assertFalse($updatedStat->getIsRead());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $updatedStat->getLastOpened());
    }

    public function testOpenIsTrackedWhenDistinctIpsStayBelowBurstThreshold(): void
    {
        $stat = $this->createStatWithBurstHits(2);

        $this->client->request(
            Request::METHOD_GET,
            '/email/'.$stat->getTrackingHash().'.gif',
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REMOTE_ADDR'     => '203.0.113.40',
            ]
        );

        $this->assertResponseIsSuccessful();

        $updatedStat = $this->em->getRepository(Stat::class)->find($stat->getId());
        $this->assertInstanceOf(Stat::class, $updatedStat);
        $this->assertTrue($updatedStat->getIsRead());
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedStat->getLastOpened());
    }

    private function createStatWithBurstHits(int $distinctIpCount): Stat
    {
        $contact = new Lead();
        $contact->setEmail('burst-scanner@mautic.test');
        $this->em->persist($contact);

        $email = $this->emailFixturesHelper->createEmail();
        $this->em->flush();

        $stat = $this->emailFixturesHelper->emulateEmailSend($contact, $email, '-80 seconds');
        $stat->setTrackingHash('burst_hash_'.$distinctIpCount);
        $this->em->persist($stat);

        // Stamp hits from one clock so a slow test setup cannot push them outside the window.
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        for ($i = 0; $i < $distinctIpCount; ++$i) {
            $ip = new IpAddress(sprintf('203.0.113.%d', 10 + $i));
            $this->em->persist($ip);

            $hit = new Hit();
            $hit->setEmail($email);
            $hit->setLead($contact);
            $hit->setIpAddress($ip);
            $hit->setDateHit(\DateTime::createFromImmutable($now));
            $hit->setCode(200);
            $hit->setUrl('https://example.test/click');
            $hit->setTrackingId('burst-track-'.$i);
            $hit->setSource('email');
            $hit->setSourceId($email->getId());
            $this->em->persist($hit);
        }

        $this->em->flush();

        return $stat;
    }
}
