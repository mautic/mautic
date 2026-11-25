<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Tests\Functional\Fixtures\EmailFixturesHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function testTrackedLinkBurstThenPixelDoesNotMarkEmailRead(): void
    {
        $fixture = $this->createEmailWithTrackedLink();
        $this->logoutUser();
        $this->client->followRedirects(false);

        foreach (['218.30.65.21', '218.30.65.22', '218.30.65.23'] as $ip) {
            $this->requestTrackedLink($fixture['redirectId'], $fixture['ct'], $ip);
            $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        }

        $this->requestTrackingPixel($fixture['trackingHash'], '218.30.65.24');
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $updatedStat = $this->em->getRepository(Stat::class)->find($fixture['statId']);
        $this->assertInstanceOf(Stat::class, $updatedStat);
        $this->assertFalse($updatedStat->getIsRead());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $updatedStat->getLastOpened());
    }

    public function testSingleTrackedLinkThenPixelMarksEmailRead(): void
    {
        $fixture = $this->createEmailWithTrackedLink();
        $this->logoutUser();
        $this->client->followRedirects(false);

        $this->requestTrackedLink($fixture['redirectId'], $fixture['ct'], '218.30.65.21');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $this->requestTrackingPixel($fixture['trackingHash'], '218.30.65.21');
        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $updatedStat = $this->em->getRepository(Stat::class)->find($fixture['statId']);
        $this->assertInstanceOf(Stat::class, $updatedStat);
        $this->assertTrue($updatedStat->getIsRead());
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedStat->getLastOpened());
    }

    /**
     * @return array{statId: int, trackingHash: string, redirectId: string, ct: string}
     */
    private function createEmailWithTrackedLink(): array
    {
        $contact = new Lead();
        $contact->setEmail('burst-click-scanner@mautic.test');
        $this->em->persist($contact);

        $email = $this->emailFixturesHelper->createEmail();
        $this->em->flush();

        $stat = $this->emailFixturesHelper->emulateEmailSend($contact, $email, '-80 seconds');
        $stat->setTrackingHash('burst_click_hash_'.$email->getId());
        $this->em->persist($stat);

        $trackable = $this->emailFixturesHelper->createEmailLink('https://example.test/click', (int) $email->getId());
        $this->em->flush();

        $redirect = $trackable->getRedirect();
        $this->assertNotNull($redirect);

        return [
            'statId'        => (int) $stat->getId(),
            'trackingHash'  => (string) $stat->getTrackingHash(),
            'redirectId'    => (string) $redirect->getRedirectId(),
            'ct'            => ClickthroughHelper::encodeArrayForUrl([
                'source'  => ['email', $email->getId()],
                'email'   => $email->getId(),
                'stat'    => $stat->getTrackingHash(),
                'lead'    => $contact->getId(),
                'channel' => ['email' => $email->getId()],
            ]),
        ];
    }

    private function requestTrackedLink(string $redirectId, string $ct, string $ip): void
    {
        $this->client->request(
            Request::METHOD_GET,
            sprintf('/r/%s?ct=%s', $redirectId, $ct),
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REMOTE_ADDR'     => $ip,
            ]
        );
    }

    private function requestTrackingPixel(string $trackingHash, string $ip): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/email/'.$trackingHash.'.gif',
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REMOTE_ADDR'     => $ip,
            ]
        );
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
