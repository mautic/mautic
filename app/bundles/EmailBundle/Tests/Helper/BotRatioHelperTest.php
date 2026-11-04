<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Mautic\PageBundle\Entity\HitRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BotRatioHelperTest extends TestCase
{
    /**
     * @param array<string> $ipDoNotTrackList
     * @param array<string> $blockedUserAgents
     */
    #[DataProvider('hitBotScenariosProvider')]
    public function testIsHitByBot(
        string $sentBefore,
        int $botHelperTimeEmailThreshold,
        string $ipAddressString,
        array $ipDoNotTrackList,
        string $userAgent,
        array $blockedUserAgents,
        float $botHelperBotRatioThreshold,
        bool $isBot,
    ): void {
        $deviceDetectorMock = $this->createMock(DeviceDetector::class);
        $deviceDetectorMock->method('parse');
        $deviceDetectorMock->method('isBot')->willReturn(false);

        $deviceDetectorFactoryMock = $this->createMock(DeviceDetectorFactoryInterface::class);
        $deviceDetectorFactoryMock->method('create')->willReturn($deviceDetectorMock);

        // Time
        $emailHitDateTime = new \DateTime();
        $emailSent        = clone $emailHitDateTime;
        $emailSent->modify($sentBefore);
        $emailStatMock = $this->createMock(Stat::class);
        $emailStatMock->expects($this->once())
            ->method('getDateSent')
            ->willReturn($emailSent);
        // IP
        $ipAddress        = new IpAddress($ipAddressString);
        $botRatioHelper   = new BotRatioHelper($deviceDetectorFactoryMock, $botHelperBotRatioThreshold, $botHelperTimeEmailThreshold, $blockedUserAgents, $ipDoNotTrackList);
        $isEvaluatedAsBot = $botRatioHelper->isHitByBot($emailStatMock, $emailHitDateTime, $ipAddress, $userAgent);
        $this->assertSame($isBot, $isEvaluatedAsBot);
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public static function hitBotScenariosProvider(): iterable
    {
        // sentBefore, botHelperTimeEmailThreshold, ipAddress, ipDoNotTrackList, userAgent, blockedUserAgents, botHelperBotRatioThreshold, isBot
        yield 'Time and IP' => ['-1 second', 2, '217.30.65.82', ['217.30.65.*'], 'Mozilla/5.0', [], 0.6, true];
        yield 'Time and User Agent' => ['-1 second', 2, '217.30.65.82', [], 'Mozilla/5.0', ['Mozilla'], 0.6, true];
        yield 'Just User Agent' => ['-3 second', 2, '217.30.65.82', [], 'Mozilla/5.0', ['Mozilla'], 0.6, false];
        yield 'All' => ['-1 second', 2, '217.30.65.82', ['217.30.65.*'], 'Mozilla/5.0', ['Mozilla'], 1.0, true];
        yield 'Just Time and IP' => ['-1 second', 2, '217.30.65.82', ['217.30.65.*'], 'Molla/5.0', ['Mozilla'], 1.0, false];
        yield 'Just Time and IP 2' => ['-1 second', 2, '217.30.65.82', ['217.30.65.*'], (string) null, ['Mozilla'], 1.0, false];
    }

    public function testBurstScannerIsDisabledByDefault(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->never())->method('countDistinctIpsForEmailAndLeadSince');

        $botRatioHelper = $this->createBotRatioHelper(hitRepository: $hitRepository);
        $emailStat      = $this->createHumanEmailStat();

        $this->assertFalse(
            $botRatioHelper->isHitByBot($emailStat, new \DateTime(), new IpAddress('203.0.113.10'), 'Mozilla/5.0')
        );
    }

    public function testBurstScannerFlagsDistributedGatewayHits(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->once())
            ->method('countDistinctIpsForEmailAndLeadSince')
            ->with(
                10,
                20,
                $this->isInstanceOf(\DateTimeInterface::class),
                $this->isInstanceOf(\DateTimeInterface::class)
            )
            ->willReturn(3);

        $botRatioHelper = $this->createBotRatioHelper(
            burstMaxDistinctIps: 3,
            hitRepository: $hitRepository
        );

        $this->assertTrue(
            $botRatioHelper->isHitByBot(
                $this->createHumanEmailStat(),
                new \DateTime(),
                new IpAddress('203.0.113.10'),
                'Mozilla/5.0'
            )
        );
    }

    public function testBurstScannerIgnoresHitsBelowDistinctIpThreshold(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->once())
            ->method('countDistinctIpsForEmailAndLeadSince')
            ->willReturn(2);

        $botRatioHelper = $this->createBotRatioHelper(
            burstMaxDistinctIps: 3,
            hitRepository: $hitRepository
        );

        $this->assertFalse(
            $botRatioHelper->isHitByBot(
                $this->createHumanEmailStat(),
                new \DateTime(),
                new IpAddress('203.0.113.10'),
                'Mozilla/5.0'
            )
        );
    }

    public function testBurstScannerSkipsWhenStatHasNoEmailOrLead(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->never())->method('countDistinctIpsForEmailAndLeadSince');

        $botRatioHelper = $this->createBotRatioHelper(
            burstMaxDistinctIps: 3,
            hitRepository: $hitRepository
        );

        $emailStat = $this->createMock(Stat::class);
        $emailStat->method('getEmail')->willReturn(null);
        $emailStat->method('getLead')->willReturn(null);
        $emailStat->method('getDateSent')->willReturn(new \DateTime('-80 seconds'));

        $this->assertFalse(
            $botRatioHelper->isHitByBot($emailStat, new \DateTime(), new IpAddress('203.0.113.10'), 'Mozilla/5.0')
        );
    }

    private function createBotRatioHelper(
        int $burstMaxDistinctIps = 0,
        ?HitRepository $hitRepository = null,
    ): BotRatioHelper {
        $deviceDetectorMock = $this->createMock(DeviceDetector::class);
        $deviceDetectorMock->method('parse');
        $deviceDetectorMock->method('isBot')->willReturn(false);

        $deviceDetectorFactoryMock = $this->createMock(DeviceDetectorFactoryInterface::class);
        $deviceDetectorFactoryMock->method('create')->willReturn($deviceDetectorMock);

        return new BotRatioHelper(
            $deviceDetectorFactoryMock,
            0.6,
            2,
            [],
            [],
            $burstMaxDistinctIps,
            30,
            $hitRepository
        );
    }

    private function createHumanEmailStat(): Stat
    {
        $email = $this->createMock(Email::class);
        $email->method('getId')->willReturn(10);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')->willReturn(20);

        $emailStat = $this->createMock(Stat::class);
        $emailStat->method('getEmail')->willReturn($email);
        $emailStat->method('getLead')->willReturn($lead);
        $emailStat->method('getDateSent')->willReturn(new \DateTime('-80 seconds'));

        return $emailStat;
    }
}
