<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test Matomo Device Detector integration with BotRatioHelper.
 */
final class BotRatioHelperMatomoTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('knownBotUserAgentsProvider')]
    public function testMatomoDetectorIdentifiesKnownBots(string $userAgent, string $expectedBotName): void
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
        $emailSent        = new \DateTime('-1 second');
        $emailStatMock->method('getDateSent')->willReturn($emailSent);
        $emailHitDateTime = new \DateTime();
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
        $emailSent      = new \DateTime('-1 second');
        $emailStatMock->method('getDateSent')->willReturn($emailSent);

        $emailHitDateTime = new \DateTime();
        $ipAddress        = new IpAddress('1.2.3.4');

        $result = $botRatioHelper->isHitByBot($emailStatMock, $emailHitDateTime, $ipAddress, $userAgent);
        $this->assertTrue($result, 'Expected bot to be detected via ratio-based logic when Matomo returns false');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function knownBotUserAgentsProvider(): iterable
    {
        yield 'Googlebot' => [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Googlebot',
        ];

        yield 'Bingbot' => [
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'BingBot',
        ];

        yield 'AhrefsBot' => [
            'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
            'aHrefs Bot',
        ];

        yield 'Facebookbot' => [
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Facebookbot',
        ];

        yield 'Twitterbot' => [
            'Twitterbot/1.0',
            'Twitterbot',
        ];

        yield 'LinkedInBot' => [
            'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)',
            'LinkedInBot',
        ];
    }
}
