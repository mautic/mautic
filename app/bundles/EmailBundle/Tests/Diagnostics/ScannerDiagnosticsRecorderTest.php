<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Diagnostics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Diagnostics\ScannerDiagnosticsRecorder;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\PageBundle\Entity\Redirect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ScannerDiagnosticsRecorderTest extends TestCase
{
    private const SECRET = 'install-secret';
    private const IP = '40.94.12.34';
    private Connection&MockObject $connection;
    private IpLookupHelper&MockObject $ipLookupHelper;
    private BotRatioHelper&MockObject $botRatioHelper;
    private LoggerInterface&MockObject $logger;
    private array $parameters;
    private ?array $inserted = null;
    private string $ip = self::IP;
    protected function setUp(): void
    {
        $this->connection     = $this->createMock(Connection::class);
        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->botRatioHelper = $this->createMock(BotRatioHelper::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->parameters     = [
            'email_scanner_diagnostics_enabled' => true,
            'anonymize_ip'                      => false,
            'secret_key'                        => self::SECRET,
        ];

        $this->ipLookupHelper->method('isRequestTrackable')->willReturn(true);
        $this->ipLookupHelper->method('isVisitorUntracked')->willReturn(false);
        $this->ipLookupHelper->method('getIpAddressFromRequest')->willReturnCallback(fn (): string => $this->ip);

        $this->connection->method('insert')->willReturnCallback(function (string $table, array $data, array $types): int {
            $this->inserted = [$table, $data, $types];

            return 1;
        });
    }

    public function testNothingIsRecordedWhenDisabled(): void
    {
        $this->parameters['email_scanner_diagnostics_enabled'] = false;
        $this->ipLookupHelper->expects($this->never())->method('isRequestTrackable');

        $recorder = $this->createRecorder();
        $recorder->record(ScannerDiagnostic::EVENT_CLICK, Request::create('/r/abc'), $this->createStat());

        $this->assertFalse($recorder->isEnabled());
        $this->assertNull($this->inserted);
    }

    public function testAClickIsRecordedWithEverythingItNeeds(): void
    {
        $this->botRatioHelper->expects($this->once())
            ->method('isHitByBot')
            ->with(
                $this->isInstanceOf(Stat::class),
                $this->isInstanceOf(\DateTimeImmutable::class),
                $this->callback(function (IpAddress $ipAddress): bool {
                    $this->assertSame(self::IP, $ipAddress->getIpAddress());

                    return true;
                }),
                'Mozilla/5.0 (Windows NT 10.0)'
            )
            ->willReturn(true);

        $request = $this->createRequest([
            'HTTP_USER_AGENT'      => 'Mozilla/5.0 (Windows NT 10.0)',
            'HTTP_ACCEPT'          => 'text/html',
            'HTTP_SEC_FETCH_MODE'  => 'navigate',
            'HTTP_SEC_CH_UA'       => '"Chromium";v="131"',
            'HTTP_COOKIE'          => 'mautic_device_id=abc',
            'HTTP_REFERER'         => 'https://mail.example.com/inbox/123',
            'HTTP_AUTHORIZATION'   => 'Bearer secret',
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
            'HTTPS'                => 'on',
        ]);

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $request, $this->createStat('-90 seconds'), $this->createRedirect(7));

        [$table, $data, $types] = $this->getInserted();

        $this->assertSame(MAUTIC_TABLE_PREFIX.'email_scanner_diagnostics', $table);
        $this->assertInstanceOf(\DateTimeImmutable::class, $data['date_added']);
        $this->assertSame('UTC', $data['date_added']->getTimezone()->getName());
        $this->assertSame(ScannerDiagnostic::EVENT_CLICK, $data['event_type']);
        $this->assertSame(5, $data['email_id']);
        $this->assertSame($this->expectedHash('send:tracking-hash'), $data['send_key']);
        $this->assertSame(7, $data['redirect_id']);
        $this->assertGreaterThanOrEqual(90, $data['seconds_after_send']);
        $this->assertLessThan(100, $data['seconds_after_send']);
        $this->assertSame($this->expectedHash('ip:'.self::IP), $data['ip_hash']);
        $this->assertSame('40.94.12.0/24', $data['ip_network']);
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0)', $data['user_agent']);
        $this->assertSame(
            [
                'accept'         => 'text/html',
                'sec-fetch-mode' => 'navigate',
                'sec-ch-ua'      => '"Chromium";v="131"',
            ],
            $data['headers']
        );
        $this->assertTrue($data['is_https']);
        $this->assertTrue($data['bot_ratio_flagged']);
        $this->assertSame(
            [
                'date_added'        => Types::DATETIME_IMMUTABLE,
                'headers'           => Types::JSON,
                'is_https'          => Types::BOOLEAN,
                'bot_ratio_flagged' => Types::BOOLEAN,
            ],
            $types
        );
    }

    public function testNoContactDataOrRawIpIsStored(): void
    {
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());

        [, $data] = $this->getInserted();

        $this->assertArrayNotHasKey('lead_id', $data);
        $this->assertArrayNotHasKey('stat_id', $data);
        $stored = json_encode($data);
        $this->assertIsString($stored);
        $this->assertStringNotContainsString(self::IP, $stored);
        $this->assertStringNotContainsString('tracking-hash', $stored);
    }

    public function testEveryEventTypeIsAccepted(): void
    {
        foreach ([ScannerDiagnostic::EVENT_HONEYPOT, ScannerDiagnostic::EVENT_CLICK, ScannerDiagnostic::EVENT_OPEN] as $eventType) {
            $this->inserted = null;
            $this->createRecorder()->record($eventType, $this->createRequest(), $this->createStat());

            $this->assertSame($eventType, $this->getInserted()[1]['event_type']);
        }
    }

    public function testAnUnknownEventTypeIsLoggedAndNotRecorded(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Unknown event type "visit"'));

        $this->createRecorder()->record('visit', $this->createRequest(), $this->createStat());

        $this->assertNull($this->inserted);
    }

    public function testARequestMauticWouldNotTrackIsNotRecorded(): void
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->method('isRequestTrackable')->willReturn(false);
        $ipLookupHelper->method('isVisitorUntracked')->willReturn(false);
        $ipLookupHelper->expects($this->never())->method('getIpAddressFromRequest');
        $this->ipLookupHelper = $ipLookupHelper;

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat());

        $this->assertNull($this->inserted);
    }

    public function testAVisitorWhoOptedOutOfTrackingIsNotRecorded(): void
    {
        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->method('isRequestTrackable')->willReturn(true);
        $ipLookupHelper->method('isVisitorUntracked')->willReturn(true);
        $ipLookupHelper->expects($this->never())->method('getIpAddressFromRequest');
        $this->ipLookupHelper = $ipLookupHelper;

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat());

        $this->assertNull($this->inserted);
    }

    public function testNothingIsRecordedWhenIpAnonymizationIsOn(): void
    {
        $this->parameters['anonymize_ip'] = true;

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat());

        $this->assertNull($this->inserted);
    }

    public function testAHitWithoutAStatHasNoEmailSendOrBotRatio(): void
    {
        $this->botRatioHelper->expects($this->never())->method('isHitByBot');

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_HONEYPOT, $this->createRequest(), null);

        [, $data] = $this->getInserted();

        $this->assertNull($data['email_id']);
        $this->assertNull($data['send_key']);
        $this->assertNull($data['redirect_id']);
        $this->assertNull($data['seconds_after_send']);
        $this->assertNull($data['bot_ratio_flagged']);
        $this->assertSame($this->expectedHash('ip:'.self::IP), $data['ip_hash']);
    }

    public function testAStatThatWasNotSentHasNoTimingOrBotRatio(): void
    {
        $this->botRatioHelper->expects($this->never())->method('isHitByBot');

        $stat = $this->createStat();
        $stat->setDateSent(null);

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $stat);

        [, $data] = $this->getInserted();

        $this->assertNull($data['seconds_after_send']);
        $this->assertNull($data['bot_ratio_flagged']);
        $this->assertSame(5, $data['email_id']);
    }

    public function testAStatWithoutEmailOrTrackingHashIsStillRecorded(): void
    {
        $stat = new Stat();
        $stat->setDateSent(new \DateTime('-10 seconds'));

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $stat);

        [, $data] = $this->getInserted();

        $this->assertNull($data['email_id']);
        $this->assertNull($data['send_key']);
        $this->assertGreaterThanOrEqual(10, $data['seconds_after_send']);
    }

    public function testASendDateInTheFutureCountsAsZeroSeconds(): void
    {
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat('+1 hour'));

        $this->assertSame(0, $this->getInserted()[1]['seconds_after_send']);
    }

    public function testTheSameSendGetsTheSameKeyAndOtherSendsAnother(): void
    {
        $recorder = $this->createRecorder();

        $recorder->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());
        $first = $this->getInserted()[1]['send_key'];

        $recorder->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat());
        $again = $this->getInserted()[1]['send_key'];

        $recorder->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat('-1 hour', 'other-hash'));
        $other = $this->getInserted()[1]['send_key'];

        $this->assertSame(16, strlen($first));
        $this->assertSame($first, $again);
        $this->assertNotSame($first, $other);
    }

    public function testHashesDependOnTheInstallSecret(): void
    {
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());
        [, $first] = $this->getInserted();

        $this->parameters['secret_key'] = 'another-install';
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());
        [, $other] = $this->getInserted();

        $this->assertNotSame($first['ip_hash'], $other['ip_hash']);
        $this->assertNotSame($first['send_key'], $other['send_key']);
    }

    public function testTheSendKeyAndIpHashNeverShareAValue(): void
    {
        $this->ip = 'tracking-hash';
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());

        [, $data] = $this->getInserted();
        $this->assertNotSame($data['send_key'], $data['ip_hash']);
    }

    public static function networkProvider(): iterable
    {
        yield 'IPv4 keeps the first 3 bytes' => ['40.94.12.34', '40.94.12.0/24'];
        yield 'IPv4 at the end of a range' => ['203.0.113.255', '203.0.113.0/24'];
        yield 'IPv6 keeps the first 48 bits' => ['2a01:111:f400:fe5d::1', '2a01:111:f400::/48'];
        yield 'IPv6 written in full' => ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:db8:85a3::/48'];
        yield 'Not an IP address' => ['tracking-hash', null];
    }

    #[DataProvider('networkProvider')]
    public function testTheNetworkPrefixIsStoredInsteadOfTheIp(string $ip, ?string $network): void
    {
        $this->ip = $ip;
        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $this->createRequest(), $this->createStat());
        $this->assertSame($network, $this->getInserted()[1]['ip_network']);
    }

    public function testLongValuesAreCutTo255Characters(): void
    {
        $request = $this->createRequest([
            'HTTP_USER_AGENT' => str_repeat('a', 400),
            'HTTP_ACCEPT'     => str_repeat('é', 400),
        ]);

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_OPEN, $request, $this->createStat());

        [, $data] = $this->getInserted();

        $this->assertSame(str_repeat('a', 255), $data['user_agent']);
        $this->assertSame(str_repeat('é', 255), $data['headers']['accept']);
    }

    public function testARequestWithoutUserAgentOrHeadersIsRecordedOverHttp(): void
    {
        $request = Request::create('/email/check/abc');
        $request->headers->replace([]);

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_HONEYPOT, $request, $this->createStat());

        [, $data] = $this->getInserted();

        $this->assertNull($data['user_agent']);
        $this->assertSame([], $data['headers']);
        $this->assertFalse($data['is_https']);
    }

    public function testEveryAllowedHeaderIsKept(): void
    {
        $server = [];
        foreach (ScannerDiagnosticsRecorder::HEADER_ALLOW_LIST as $name) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = 'value of '.$name;
        }

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest($server), $this->createStat());

        $headers = $this->getInserted()[1]['headers'];

        $this->assertCount(count(ScannerDiagnosticsRecorder::HEADER_ALLOW_LIST), $headers);
        foreach (ScannerDiagnosticsRecorder::HEADER_ALLOW_LIST as $name) {
            $this->assertSame('value of '.$name, $headers[strtolower($name)]);
        }
    }

    public function testADatabaseErrorIsLoggedAndDoesNotBreakTracking(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('insert')->willThrowException(new \RuntimeException('Table does not exist'));
        $this->connection = $connection;

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Email scanner diagnostics could not be recorded: Table does not exist',
                $this->callback(function (array $context): bool {
                    $this->assertSame(ScannerDiagnostic::EVENT_CLICK, $context['event_type']);
                    $this->assertInstanceOf(\RuntimeException::class, $context['exception']);

                    return true;
                })
            );

        $this->createRecorder()->record(ScannerDiagnostic::EVENT_CLICK, $this->createRequest(), $this->createStat());
    }

    private function createRecorder(): ScannerDiagnosticsRecorder
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(fn (string $name): mixed => $this->parameters[$name] ?? null);

        return new ScannerDiagnosticsRecorder(
            $this->connection,
            $coreParametersHelper,
            $this->ipLookupHelper,
            $this->botRatioHelper,
            $this->logger,
        );
    }

    private function createRequest(array $server = []): Request
    {
        $request = Request::create('/r/abc', 'GET', [], [], [], $server);

        foreach (['Accept', 'Accept-Language', 'Accept-Charset', 'User-Agent'] as $name) {
            if (!isset($server['HTTP_'.strtoupper(str_replace('-', '_', $name))])) {
                $request->headers->remove($name);
            }
        }

        return $request;
    }

    private function createStat(string $sent = '-1 hour', string $trackingHash = 'tracking-hash'): Stat
    {
        $email = new Email();
        $email->setId(5);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setTrackingHash($trackingHash);
        $stat->setDateSent(new \DateTime($sent));

        return $stat;
    }

    private function createRedirect(int $id): Redirect
    {
        $redirect = $this->createMock(Redirect::class);
        $redirect->method('getId')->willReturn($id);

        return $redirect;
    }

    private function expectedHash(string $message): string
    {
        return substr(hash_hmac('sha256', $message, self::SECRET), 0, 16);
    }

    private function getInserted(): array
    {
        $this->assertNotNull($this->inserted, 'Nothing was recorded');
        return $this->inserted;
    }
}
