<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Diagnostics\ScannerDiagnosticsRecorder;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ScannerDiagnosticsRecorderFunctionalTest extends MauticMysqlTestCase
{
    private const SCANNER_IP = '40.94.12.34';
    private const BROWSER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36';

    protected function setUp(): void
    {
        $this->configParams['email_scanner_diagnostics_enabled'] = 'testNothingIsStoredWhenDisabled' !== $this->name();
        $this->configParams['anonymize_ip']                      = false;

        if ('testAnIpOnTheDoNotTrackListIsNotStored' === $this->name()) {
            $this->configParams['do_not_track_ips'] = [self::SCANNER_IP];
        }

        parent::setUp();
    }

    public function testAClickIsStoredInTheDatabase(): void
    {
        $stat     = $this->createStat();
        $redirect = $this->createRedirect();
        $request  = $this->pushRequest([
            'HTTP_USER_AGENT'     => self::BROWSER_USER_AGENT,
            'HTTP_ACCEPT'         => 'text/html',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
            'HTTP_COOKIE'         => 'mautic_device_id=abc',
            'HTTPS'               => 'on',
        ]);

        $this->getRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $request, $stat, $redirect);
        $row = $this->fetchOnlyRow();

        $this->assertSame(ScannerDiagnostic::EVENT_CLICK, $row['event_type']);
        $this->assertSame($stat->getEmail()?->getId(), (int) $row['email_id']);
        $this->assertSame($redirect->getId(), (int) $row['redirect_id']);
        $this->assertSame(16, strlen((string) $row['send_key']));
        $this->assertSame(16, strlen((string) $row['ip_hash']));
        $this->assertSame('40.94.12.0/24', $row['ip_network']);
        $this->assertSame(self::BROWSER_USER_AGENT, $row['user_agent']);
        $this->assertSame(['accept' => 'text/html', 'sec-fetch-mode' => 'navigate'], json_decode((string) $row['headers'], true));
        $this->assertSame(1, (int) $row['is_https']);
        $this->assertSame(0, (int) $row['bot_ratio_flagged']);
        $this->assertGreaterThanOrEqual(120, (int) $row['seconds_after_send']);
        $this->assertLessThan(180, (int) $row['seconds_after_send']);
        $this->assertEqualsWithDelta(time(), (new \DateTimeImmutable((string) $row['date_added'], new \DateTimeZone('UTC')))->getTimestamp(), 60);
    }

    public function testTheRowCanBeReadThroughTheEntity(): void
    {
        $stat = $this->createStat();
        $this->getRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->pushRequest(['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT]), $stat);

        $diagnostics = $this->em->getRepository(ScannerDiagnostic::class)->findAll();

        $this->assertCount(1, $diagnostics);
        $this->assertSame(ScannerDiagnostic::EVENT_OPEN, $diagnostics[0]->getEventType());
        $this->assertSame($stat->getEmail()?->getId(), $diagnostics[0]->getEmail()?->getId());
        $this->assertFalse($diagnostics[0]->isHttps());
    }

    public function testEventsOfOneSendShareTheirSendKey(): void
    {
        $stat      = $this->createStat();
        $otherStat = $this->createStat('other-tracking-hash');
        $request   = $this->pushRequest(['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT]);
        $recorder  = $this->getRecorder();

        $recorder->record(ScannerDiagnostic::EVENT_HONEYPOT, $request, $stat);
        $recorder->record(ScannerDiagnostic::EVENT_CLICK, $request, $stat, $this->createRedirect());
        $recorder->record(ScannerDiagnostic::EVENT_OPEN, $request, $otherStat);

        $keys = $this->connection->fetchFirstColumn('SELECT send_key FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME.' ORDER BY id');

        $this->assertCount(3, $keys);
        $this->assertSame($keys[0], $keys[1]);
        $this->assertNotSame($keys[0], $keys[2]);
    }

    public static function untrackedRequestProvider(): iterable
    {
        yield 'Known bot' => [['HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)']];
        yield 'Do Not Track' => [['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT, 'HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT, 'HTTP_SEC_GPC' => '1']];
        yield 'Prefetch' => [['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT, 'HTTP_SEC_PURPOSE' => 'prefetch']];
    }

    
    #[DataProvider('untrackedRequestProvider')]
    public function testARequestMauticDoesNotTrackIsNotStored(array $server): void
    {
        $this->getRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->pushRequest($server), $this->createStat());

        $this->assertSame(0, $this->countRows());
    }

    public function testAHeadRequestIsNotStored(): void
    {
        $this->getRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->pushRequest(['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT], 'HEAD'), $this->createStat());
        $this->assertSame(0, $this->countRows());
    }

    public function testAnIpOnTheDoNotTrackListIsNotStored(): void
    {
        $this->getRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->pushRequest(['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT]), $this->createStat());
        $this->assertSame(0, $this->countRows());
    }

    public function testNothingIsStoredWhenDisabled(): void
    {
        $recorder = $this->getRecorder();
        $recorder->record(ScannerDiagnostic::EVENT_CLICK, $this->pushRequest(['HTTP_USER_AGENT' => self::BROWSER_USER_AGENT]), $this->createStat());

        $this->assertFalse($recorder->isEnabled());
        $this->assertSame(0, $this->countRows());
    }

    private function getRecorder(): ScannerDiagnosticsRecorder
    {
        return self::getContainer()->get(ScannerDiagnosticsRecorder::class);
    }

    private function pushRequest(array $server, string $method = 'GET'): Request
    {
        $request = Request::create('/r/abc', $method, [], [], [], array_merge(['REMOTE_ADDR' => self::SCANNER_IP], $server));
        foreach (['Accept', 'Accept-Language', 'Accept-Charset'] as $name) {
            if (!isset($server['HTTP_'.strtoupper(str_replace('-', '_', $name))])) {
                $request->headers->remove($name);
            }
        }

        self::getContainer()->get(RequestStack::class)->push($request);
        return $request;
    }

    private function createStat(string $trackingHash = 'scanner-diagnostics-hash'): Stat
    {
        $email = new Email();
        $email->setName('Scanner diagnostics');
        $email->setSubject('Scanner diagnostics');
        $email->setEmailType('template');
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setEmailAddress('scanned@example.com');
        $stat->setTrackingHash($trackingHash);
        $stat->setDateSent(new \DateTime('-2 minutes'));
        $this->em->persist($stat);
        $this->em->flush();

        return $stat;
    }

    private function createRedirect(): Redirect
    {
        $redirect = new Redirect();
        $redirect->setRedirectId(uniqid());
        $redirect->setUrl('https://example.com');
        $this->em->persist($redirect);
        $this->em->flush();

        return $redirect;
    }


    private function fetchOnlyRow(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME);
        $this->assertCount(1, $rows);
        return $rows[0];
    }

    private function countRows(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME);
    }
}
