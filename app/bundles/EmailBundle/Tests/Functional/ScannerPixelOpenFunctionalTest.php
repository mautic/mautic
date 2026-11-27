<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ScannerPixelOpenFunctionalTest extends MauticMysqlTestCase
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko)';

    private const IP = '203.0.113.80';

    private const HIT_TRANSPORT = 'messenger.transport.hit';

    protected function setUp(): void
    {
        $this->configParams['email_scanner_diagnostics_enabled'] = 'testNothingIsRecordedWhenDiagnosticsAreOff' !== $this->name();
        $this->configParams['anonymize_ip']                      = false;

        if ('testTheOpenIsRecordedWhenTheRequestArrivesEvenIfAWorkerHandlesItLater' === $this->name()) {
            $this->configParams['messenger_dsn_hit'] = 'in-memory://default';
        }

        parent::setUp();
    }

    public function testAPixelRequestIsRecordedAsAnOpen(): void
    {
        $stat = $this->createSentEmail('pixel@example.com');

        $this->openEmail($stat, self::IP, [
            'HTTP_ACCEPT'         => 'image/avif,image/webp,*/*',
            'HTTP_ACCEPT_LANGUAGE' => 'en',
            'HTTP_SEC_FETCH_DEST' => 'image',
            'HTTP_SEC_FETCH_MODE' => 'no-cors',
            'HTTP_COOKIE'         => 'session=must-not-be-stored',
        ]);

        $rows = $this->fetchRows();
        $this->assertCount(1, $rows);
        $this->assertSame(ScannerDiagnostic::EVENT_OPEN, $rows[0]['event_type']);
        $this->assertSame($stat->getEmail()?->getId(), (int) $rows[0]['email_id']);
        $this->assertNull($rows[0]['redirect_id']);
        $this->assertNotNull($rows[0]['send_key']);
        $this->assertSame('203.0.113.0/24', $rows[0]['ip_network']);
        $this->assertSame(self::USER_AGENT, $rows[0]['user_agent']);
        $this->assertGreaterThanOrEqual(80, (int) $rows[0]['seconds_after_send']);
        $this->assertNotNull($rows[0]['bot_ratio_flagged']);

        $headers = json_decode((string) $rows[0]['headers'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('en', $headers['accept-language']);
        $this->assertSame('image', $headers['sec-fetch-dest']);
        $this->assertSame('no-cors', $headers['sec-fetch-mode']);
        $this->assertStringNotContainsString('must-not-be-stored', (string) $rows[0]['headers'], 'only allow-listed headers are kept');
    }

    public function testTheOpenIsStillCountedExactlyAsBefore(): void
    {
        $stat    = $this->createSentEmail('counted@example.com');
        $emailId = (int) $stat->getEmail()?->getId();

        $this->openEmail($stat);

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertTrue($stat->getIsRead());
        $this->assertSame(1, $stat->getOpenCount());
        $this->assertInstanceOf(\DateTimeInterface::class, $stat->getLastOpened());
        $this->assertSame(1, (int) $this->em->find(Email::class, $emailId)?->getReadCount());
        $this->assertCount(1, $this->fetchRows());
    }

    public function testEveryRequestOfASendIsRecordedWithTheSameSendKey(): void
    {
        $stat = $this->createSentEmail('twice@example.com');

        $this->openEmail($stat, '203.0.113.80');
        $this->openEmail($stat, '198.51.100.7');

        $rows = $this->fetchRows();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]['send_key'], $rows[1]['send_key']);
        $this->assertNotSame($rows[0]['ip_hash'], $rows[1]['ip_hash']);
        $this->assertSame(['open', 'open'], array_column($rows, 'event_type'));
    }

    public function testAnOpenAndAHoneypotHitOfTheSameSendCanBeJoined(): void
    {
        $stat = $this->createSentEmail('joined@example.com');

        $this->client->request(Request::METHOD_GET, '/email/check/'.$stat->getTrackingHash(), [], [], ['HTTP_USER_AGENT' => self::USER_AGENT, 'REMOTE_ADDR' => self::IP]);
        $this->assertResponseIsSuccessful();
        $this->em->clear();

        $notRead = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $notRead);
        $this->assertFalse($notRead->getIsRead(), 'the honeypot is not an open');

        $this->openEmail($stat);

        $rows = $this->fetchRows();
        $this->assertSame([ScannerDiagnostic::EVENT_HONEYPOT, ScannerDiagnostic::EVENT_OPEN], array_column($rows, 'event_type'));
        $this->assertSame($rows[0]['send_key'], $rows[1]['send_key']);
    }

    public function testNothingIsRecordedWhenDiagnosticsAreOff(): void
    {
        $stat = $this->createSentEmail('off@example.com');

        $this->openEmail($stat);

        $this->assertCount(0, $this->fetchRows());

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertTrue($stat->getIsRead(), 'the open is counted as it always was');
    }

    public function testAContactWhoTurnedTrackingOffIsNotRecorded(): void
    {
        $stat = $this->createSentEmail('opted-out@example.com');
        $this->setActivityTracking($stat, false);

        $this->openEmail($stat);

        $this->assertCount(0, $this->fetchRows());

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead());
    }

    public function testAnUnknownHashIsServedTheImageAndNotRecorded(): void
    {
        $this->createSentEmail('known@example.com');

        $this->client->request(Request::METHOD_GET, '/email/does-not-exist.gif', [], [], ['HTTP_USER_AGENT' => self::USER_AGENT, 'REMOTE_ADDR' => self::IP]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    public function testAHeadRequestIsServedTheImageAndNotRecorded(): void
    {
        $stat = $this->createSentEmail('head@example.com');

        $this->client->request(Request::METHOD_HEAD, '/email/'.$stat->getTrackingHash().'.gif', [], [], ['HTTP_USER_AGENT' => self::USER_AGENT, 'REMOTE_ADDR' => self::IP]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    public function testABotMauticAlreadyIgnoresIsNotRecorded(): void
    {
        $stat = $this->createSentEmail('bot@example.com');

        // An address no other test opens from: the bot check is skipped for an address already seen in this process
        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], [
            'HTTP_USER_AGENT' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
            'REMOTE_ADDR'     => '203.0.113.99',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->fetchRows());
    }

    public function testTheOpenIsRecordedWhenTheRequestArrivesEvenIfAWorkerHandlesItLater(): void
    {
        $stat = $this->createSentEmail('queued@example.com');

        $this->openEmail($stat);

        $this->assertCount(1, $this->getHitTransport()->getSent(), 'the open is queued');
        $this->assertCount(1, $this->fetchRows(), 'and recorded without waiting for the worker');

        $notRead = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $notRead);
        $this->assertFalse($notRead->getIsRead(), 'the open itself is still waiting for the worker');

        $handler = self::getContainer()->get(EmailHitNotificationHandler::class);
        foreach ($this->getHitTransport()->getSent() as $envelope) {
            $message = $envelope->getMessage();
            $this->assertInstanceOf(EmailHitNotification::class, $message);
            $handler($message);
        }
        $this->em->clear();

        $read = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $read);
        $this->assertTrue($read->getIsRead());
        $this->assertCount(1, $this->fetchRows(), 'the worker does not record it a second time');
    }

    private function createSentEmail(string $address): Stat
    {
        $contact = new Lead();
        $contact->setEmail($address);
        $this->em->persist($contact);

        $email = new Email();
        $email->setName('Scanner pixel open');
        $email->setSubject('Scanner pixel open');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress($address);
        $stat->setTrackingHash(substr(md5($address), 0, 20));
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $this->em->persist($stat);
        $this->em->flush();

        $this->logoutUser();

        return $stat;
    }

    private function setActivityTracking(Stat $stat, bool $allowed): void
    {
        $contact = $this->em->find(Lead::class, $stat->getLead()?->getId());
        $this->assertInstanceOf(Lead::class, $contact);

        self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, $allowed, 'test');
    }

    /**
     * @param array<string, string> $server
     */
    private function openEmail(Stat $stat, string $ip = self::IP, array $server = []): void
    {
        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], $server + [
            'HTTP_USER_AGENT' => self::USER_AGENT,
            'REMOTE_ADDR'     => $ip,
        ]);

        $this->assertResponseIsSuccessful('the image is served either way');
        $this->em->clear();
    }

    private function getHitTransport(): InMemoryTransport
    {
        $transport = self::getContainer()->get(self::HIT_TRANSPORT);
        $this->assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM '.MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME.' ORDER BY id');
    }
}
