<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(IpLookupHelper::class)]
final class IpLookupHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DeviceDetector
     */
    private \PHPUnit\Framework\MockObject\MockObject $deviceDetector;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DeviceDetectorFactoryInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $deviceDetectorFactory;

    protected function setUp(): void
    {
        $this->deviceDetectorFactory = $this->createMock(DeviceDetectorFactoryInterface::class);
        $this->deviceDetector        = $this->createMock(DeviceDetector::class);

        defined('MAUTIC_ENV') || define('MAUTIC_ENV', 'test');
    }

    public function testDeviceDetectorBotsDetectionTrue(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);

        $this->deviceDetector
            ->method('isBot')
            ->willReturn(true);

        $ip = $this->getIpHelper($request);
        $this->assertFalse($ip->getIpAddress()->isTrackable());
    }

    public function testDeviceDetectorBotsDetectionFalse(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.53']);

        $this->deviceDetector
            ->method('isBot')
            ->willReturn(false);

        $ip = $this->getIpHelper($request);
        $this->assertTrue($ip->getIpAddress()->isTrackable());
    }

    #[TestDox('Check if IP outside a request that local IP is returned')]
    public function testLocalIpIsReturnedWhenNotInRequestScope(): void
    {
        $ip = $this->getIpHelper()->getIpAddress();

        $this->assertEquals('127.0.0.1', $ip->getIpAddress());
    }

    #[TestDox('Check that the first IP is returned when the request is a proxy')]
    public function testClientIpIsReturnedFromProxy(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_X_FORWARDED_FOR' => '73.77.245.52,10.8.0.2,192.168.0.1']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('73.77.245.52', $ip->getIpAddress());
    }

    #[TestDox('Check that the first IP is returned with a web proxy')]
    public function testClientIpIsReturnedFromRequest(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.53']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('73.77.245.53', $ip->getIpAddress());
    }

    #[TestDox('Check that a supplied request is used without a current request stack entry')]
    public function testClientIpIsReturnedFromSuppliedRequest(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.54']);
        $ip      = $this->getIpHelper()->getIpAddressForRequest($request);

        $this->assertEquals('73.77.245.54', $ip->getIpAddress());
    }

    #[TestDox('Check that email tracking can skip generic device-detector bot classification')]
    public function testEmailTrackingCanSkipDeviceDetectorBotClassification(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.55']);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (via ggpht.com GoogleImageProxy)');

        $this->deviceDetector->expects($this->never())->method('parse');

        $this->assertTrue($this->getIpHelper()->isRequestTrackableForRequest($request, false));
    }

    #[TestDox('Check that email tracking still rejects ordinary device-detector bots')]
    public function testEmailTrackingCanUseDeviceDetectorBotClassification(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.55']);
        $request->headers->set('User-Agent', 'KnownBot/1.0');

        $this->deviceDetector->expects($this->once())->method('parse');
        $this->deviceDetector->method('isBot')->willReturn(true);

        $this->assertFalse($this->getIpHelper()->isRequestTrackableForRequest($request));
    }

    #[TestDox('Check that the user-agent-dependent IP cache is reset between worker messages')]
    public function testResetClearsUserAgentDependentIpCache(): void
    {
        $this->deviceDetector
            ->method('isBot')
            ->willReturnOnConsecutiveCalls(true, false);

        $helper  = $this->getIpHelper();
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.56']);
        $request->headers->set('User-Agent', 'Mozilla/5.0');

        $this->assertFalse($helper->isRequestTrackableForRequest($request));

        $helper->reset();

        $this->assertTrue($helper->isRequestTrackableForRequest($request));
    }

    #[TestDox('Keep User-Agent classification separate from the persisted IP entity in either lookup order')]
    public function testRequestTrackabilityIsIndependentOfLookupOrder(): void
    {
        $persistedIp = new IpAddress('73.77.245.57');
        $repository  = $this->createMock(IpAddressRepository::class);
        $repository
            ->method('__call')
            ->with('findOneByIpAddress')
            ->willReturn($persistedIp);

        $botDetector    = $this->createMock(DeviceDetector::class);
        $normalDetector = $this->createMock(DeviceDetector::class);
        $botDetector->method('isBot')->willReturn(true);
        $normalDetector->method('isBot')->willReturn(false);

        $helper = $this->getIpHelper(
            mockRepository: $repository,
            deviceDetectorFactoryCallback: fn (string $userAgent): DeviceDetector => str_contains($userAgent, 'KnownBot') ? $botDetector : $normalDetector,
        );
        $botRequest = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.57']);
        $botRequest->headers->set('User-Agent', 'KnownBot/1.0');
        $normalRequest = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.57']);
        $normalRequest->headers->set('User-Agent', 'Mozilla/5.0');

        $this->assertFalse($helper->isRequestTrackableForRequest($botRequest));
        $this->assertTrue($helper->isRequestTrackableForRequest($normalRequest));

        $helper->reset();

        $this->assertTrue($helper->isRequestTrackableForRequest($normalRequest));
        $this->assertFalse($helper->isRequestTrackableForRequest($botRequest));
    }

    #[TestDox('Check that a local IP is returned for internal IPs')]
    public function testLocalIpIsReturnedForInternalNetworkIp(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
        $ip      = $this->getIpHelper($request)->getIpAddress();

        $this->assertEquals('127.0.0.1', $ip->getIpAddress());
    }

    #[TestDox('Check that internal IP is returned if track_private_ip_ranges is set to true')]
    public function testInternalNetworkIpIsReturnedIfSetToTrack(): void
    {
        $request                  = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.0.1']);
        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper
            ->method('get')
            ->willReturnCallback(
                fn ($param, $defaultValue) => 'track_private_ip_ranges' === $param ? true : $defaultValue
            );
        $ip = $this->getIpHelper($request, $mockCoreParametersHelper)->getIpAddress();

        $this->assertEquals('192.168.0.1', $ip->getIpAddress());
    }

    #[TestDox('Check that prefetch requests are not trackable')]
    public function testIsRequestTrackableWithPrefetchHeader(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Purpose', 'prefetch');

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertFalse($result);
    }

    #[TestDox('Check that prerender requests are not trackable')]
    public function testIsRequestTrackableWithSecPurposePrerenderHeader(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Sec-Purpose', 'prerender');

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertFalse($result);
    }

    #[TestDox('Check that GPC requests are not trackable')]
    public function testIsRequestTrackableWithGpcHeader(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Sec-GPC', '1');

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertFalse($result);
    }

    #[TestDox('Check that DNT requests are not trackable')]
    public function testIsRequestTrackableWithDntHeader(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('DNT', '1');

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertFalse($result);
    }

    #[TestDox('Check that HEAD requests are not trackable')]
    public function testIsRequestTrackableWithHeadMethod(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->setMethod('HEAD');

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertFalse($result);
    }

    #[TestDox('Check that normal requests are trackable')]
    public function testIsRequestTrackableReturnsTrueForNormalRequest(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);

        $result = $this->getIpHelper($request)->isRequestTrackable();

        $this->assertTrue($result);
    }

    #[TestDox('Check that requests without request context fall back to IP trackability')]
    public function testIsRequestTrackableWithoutRequest(): void
    {
        $result = $this->getIpHelper()->isRequestTrackable();

        // Returns true since there's no request to check and the IP (127.0.0.1) is trackable
        $this->assertTrue($result);
    }

    private function getIpHelper(
        ?Request $request = null,
        ?CoreParametersHelper $mockCoreParametersHelper = null,
        ?IpAddressRepository $mockRepository = null,
        ?callable $deviceDetectorFactoryCallback = null,
    ): IpLookupHelper {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        if (null === $mockRepository) {
            $mockRepository = $this->createMock(IpAddressRepository::class);
            $mockRepository
                ->method('__call')
                ->with('findOneByIpAddress')
                ->willReturn(null);
        }

        if (null === $mockCoreParametersHelper) {
            $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
            $mockCoreParametersHelper
                ->method('get')
                ->willReturn(null);
        }

        $this->deviceDetectorFactory
            ->method('create')
            ->willReturnCallback(
                $deviceDetectorFactoryCallback ?? fn (): \PHPUnit\Framework\MockObject\MockObject => $this->deviceDetector
            );

        $helper = new IpLookupHelper($requestStack, $mockRepository, $mockCoreParametersHelper, $this->deviceDetectorFactory);
        $helper->reset();

        return $helper;
    }
}
