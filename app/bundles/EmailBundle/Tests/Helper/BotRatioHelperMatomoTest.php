<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test Matomo Device Detector integration with BotRatioHelper.
 */
final class BotRatioHelperMatomoTest extends TestCase
{
    #[DataProvider('knownBotUserAgentsProvider')]
    public function testPositiveDetectorResultCountsAsBot(string $userAgent): void
    {
        $deviceDetectorMock = $this->createMock(DeviceDetector::class);
        $deviceDetectorMock->expects($this->once())
            ->method('parse');
        $deviceDetectorMock->expects($this->once())
            ->method('isBot')
            ->willReturn(true);

        $deviceDetectorFactoryMock = $this->createMock(DeviceDetectorFactoryInterface::class);
        $deviceDetectorFactoryMock->expects($this->once())
            ->method('create')
            ->with($userAgent)
            ->willReturn($deviceDetectorMock);

        $botRatioHelper = new BotRatioHelper($deviceDetectorFactoryMock);

        $emailStatMock    = $this->createMock(Stat::class);
        $emailSent        = new \DateTime('2024-01-01 00:00:00');
        $emailStatMock->method('getDateSent')->willReturn($emailSent);
        $emailHitDateTime = new \DateTime('2024-01-01 00:00:01');
        $ipAddress        = new IpAddress('1.2.3.4');

        $result = $botRatioHelper->isHitByBot($emailStatMock, $emailHitDateTime, $ipAddress, $userAgent);
        $this->assertTrue($result, "Expected bot to be detected for user agent: {$userAgent}");
    }

    public function testFallbackToRatioBasedWhenMatomoReturnsNegative(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $deviceDetectorMock = $this->createMock(DeviceDetector::class);
        $deviceDetectorMock->method('parse');
        $deviceDetectorMock->method('isBot')->willReturn(false);

        $deviceDetectorFactoryMock = $this->createMock(DeviceDetectorFactoryInterface::class);
        $deviceDetectorFactoryMock->method('create')->willReturn($deviceDetectorMock);

        $botRatioHelper = new BotRatioHelper($deviceDetectorFactoryMock, 0.6, 2, [], ['1.2.3.*']);
        $emailStatMock  = $this->createMock(Stat::class);
        $emailStatMock->method('getDateSent')->willReturn(new \DateTime('2024-01-01 00:00:00'));

        $emailHitDateTime = new \DateTime('2024-01-01 00:00:01');
        $ipAddress        = new IpAddress('1.2.3.4');

        $result = $botRatioHelper->isHitByBot($emailStatMock, $emailHitDateTime, $ipAddress, $userAgent);
        $this->assertTrue($result, 'Expected bot to be detected via ratio-based logic when Matomo returns false');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function knownBotUserAgentsProvider(): iterable
    {
        yield 'Googlebot' => [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ];

        yield 'Bingbot' => [
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        ];

        yield 'AhrefsBot' => [
            'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        ];

        yield 'Facebookbot' => [
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        ];

        yield 'Twitterbot' => [
            'Twitterbot/1.0',
        ];

        yield 'LinkedInBot' => [
            'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
        ];
    }
}
