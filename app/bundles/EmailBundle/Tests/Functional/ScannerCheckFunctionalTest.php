<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ScannerCheckFunctionalTest extends MauticMysqlTestCase
{
    private const SCANNER_IP = '40.94.12.34';

    private const SCANNER_USER_AGENT = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';

    protected function setUp(): void
    {
        $this->configParams['email_scanner_diagnostics_enabled'] = 'testNothingIsRecordedWhenDiagnosticsAreOff' !== $this->name();
        $this->configParams['anonymize_ip']                      = false;

        parent::setUp();
    }

    public function testTheRouteIsPublicAndGeneratesTheDocumentedPath(): void
    {
        $router = self::getContainer()->get(RouterInterface::class);

        $this->assertSame('/email/check/abc123', $router->generate('mautic_email_scanner_check', ['idHash' => 'abc123']));
    }

    public function testAHoneypotHitIsRecordedAndAnswersWithAnEmptyPage(): void
    {
        $stat = $this->createStat();

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer());

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertSame('', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertCount(0, $response->headers->getCookies(), 'no tracking cookie is set');

        $rows = $this->fetchRows();
        $this->assertCount(1, $rows);
        $this->assertSame(ScannerDiagnostic::EVENT_HONEYPOT, $rows[0]['event_type']);
        $this->assertSame($stat->getEmail()?->getId(), (int) $rows[0]['email_id']);
        $this->assertNull($rows[0]['redirect_id']);
        $this->assertSame('40.94.12.0/24', $rows[0]['ip_network']);
        $this->assertSame(self::SCANNER_USER_AGENT, $rows[0]['user_agent']);
        $this->assertGreaterThanOrEqual(80, (int) $rows[0]['seconds_after_send']);
    }

    public function testAHoneypotHitIsNeitherAnOpenNorAClickAndCreatesNoContact(): void
    {
        $stat     = $this->createStat();
        $statId   = $stat->getId();
        $email    = $stat->getEmail();
        $contacts = $this->countRows('leads');
        $hits     = $this->countRows('page_hits');
        $ips      = $this->countRows('ip_addresses');

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer());

        $this->assertResponseIsSuccessful();
        $this->em->clear();

        $stat = $this->em->find(Stat::class, $statId);
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead());
        $this->assertSame(0, $stat->getOpenCount());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $stat->getLastOpened());
        $this->assertNotInstanceOf(\Mautic\CoreBundle\Entity\IpAddress::class, $stat->getIpAddress());
        $this->assertSame(0, (int) $this->em->find(Email::class, $email?->getId())?->getReadCount());
        $this->assertSame($contacts, $this->countRows('leads'));
        $this->assertSame($hits, $this->countRows('page_hits'));
        $this->assertSame($ips, $this->countRows('ip_addresses'), 'the scanner IP address is not stored');
        $this->assertSame(0, $this->countRows('email_stats_devices'));
    }

    public function testHitsOfOneSendShareTheirSendKey(): void
    {
        $stat = $this->createStat();

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer());
        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer('40.94.99.1'));

        $rows = $this->fetchRows();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]['send_key'], $rows[1]['send_key']);
        $this->assertNotSame($rows[0]['ip_hash'], $rows[1]['ip_hash']);
    }

    public function testNothingIsRecordedWhenDiagnosticsAreOff(): void
    {
        $stat = $this->createStat();

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer());

        $this->assertResponseIsSuccessful('emails sent while it was on still lead here');
        $this->assertSame('', $this->client->getResponse()->getContent());
        $this->assertCount(0, $this->fetchRows());
    }

    public function testAnUnknownHashGetsTheSameAnswerAndIsNotRecorded(): void
    {
        $this->createStat();

        $this->client->request(Request::METHOD_GET, '/email/check/does-not-exist', [], [], $this->getServer());

        $this->assertResponseIsSuccessful();
        $this->assertSame('', $this->client->getResponse()->getContent());
        $this->assertSame('noindex, nofollow', $this->client->getResponse()->headers->get('X-Robots-Tag'));
        $this->assertCount(0, $this->fetchRows());
    }

    public function testAContactWhoTurnedTrackingOffIsNotRecorded(): void
    {
        $stat = $this->createStat();
        self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($stat->getLead(), false, 'test');

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer());

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    public function testABotMauticAlreadyIgnoresIsNotRecorded(): void
    {
        $stat = $this->createStat();

        $this->client->request(Request::METHOD_GET, $this->getUrl($stat), [], [], $this->getServer(self::SCANNER_IP, 'Googlebot/2.1 (+http://www.google.com/bot.html)'));

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    public function testAHeadRequestIsAnsweredButNotRecorded(): void
    {
        $stat = $this->createStat();

        $this->client->request(Request::METHOD_HEAD, $this->getUrl($stat), [], [], $this->getServer());

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    private function getUrl(Stat $stat): string
    {
        return '/email/check/'.$stat->getTrackingHash();
    }

    /**
     * @return array<string, string>
     */
    private function getServer(string $ip = self::SCANNER_IP, string $userAgent = self::SCANNER_USER_AGENT): array
    {
        return [
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR'     => $ip,
        ];
    }

    private function createStat(): Stat
    {
        $contact = new Lead();
        $contact->setEmail('scanned@example.com');
        $this->em->persist($contact);

        $email = new Email();
        $email->setName('Scanner check');
        $email->setSubject('Scanner check');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress('scanned@example.com');
        $stat->setTrackingHash('scanner-check-hash');
        $stat->setDateSent(new \DateTime('-80 seconds'));
        $this->em->persist($stat);
        $this->em->flush();

        $this->logoutUser();

        return $stat;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME.' ORDER BY id');
    }

    private function countRows(string $table): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.$table);
    }
}
