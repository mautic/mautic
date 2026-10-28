<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use DeviceDetector\DeviceDetector;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testIsRequestTrackableWithCombinedSecPurposeHeader(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Sec-Purpose', 'prefetch;prerender');

        $this->assertFalse($this->getIpHelper($request)->isRequestTrackable());
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

    /**
     * @return iterable<string, array{array<string, string>, string, bool}>
     */
    public static function countableRequestProvider(): iterable
    {
        yield 'normal request' => [[], 'GET', true];
        yield 'Do Not Track' => [['DNT' => '1'], 'GET', true];
        yield 'Global Privacy Control' => [['Sec-GPC' => '1'], 'GET', true];
        yield 'prefetch' => [['Purpose' => 'prefetch'], 'GET', false];
        yield 'prerender' => [['Sec-Purpose' => 'prerender'], 'GET', false];
        yield 'combined prefetch and prerender' => [['Sec-Purpose' => 'prefetch;prerender'], 'GET', false];
        yield 'prefetch with a parameter' => [['Sec-Purpose' => 'prefetch; anonymous-client-ip'], 'GET', false];
        yield 'HEAD request' => [[], 'HEAD', false];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('countableRequestProvider')]
    public function testIsRequestCountable(array $headers, string $method, bool $countable): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52', 'REQUEST_METHOD' => $method]);
        $request->headers->add($headers);

        $this->assertSame($countable, $this->getIpHelper($request)->isRequestCountable());
    }

    public function testIsRequestCountableIsFalseForBots(): void
    {
        $this->deviceDetector->method('isBot')->willReturn(true);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('DNT', '1');

        $this->assertFalse($this->getIpHelper($request)->isRequestCountable());
    }

    public function testIsRequestCountableIsFalseForExcludedIpAddresses(): void
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(static fn (string $name): ?array => 'do_not_track_ips' === $name ? ['73.77.245.0/24'] : null);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Sec-GPC', '1');

        $this->assertFalse($this->getIpHelper($request, $coreParametersHelper)->isRequestCountable());
    }

    public function testIsRequestCountableIsFalseForAnExcludedPrivateAddress(): void
    {
        // Private ranges aren't tracked by default, so the address would otherwise be checked as 127.0.0.1
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(static fn (string $name): ?array => 'do_not_track_ips' === $name ? ['10.0.0.5'] : null);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '10.0.0.5']);
        $request->headers->set('DNT', '1');

        $this->assertFalse($this->getIpHelper($request, $coreParametersHelper)->isRequestCountable());
    }

    public function testIsRequestCountableDoesNotStoreTheIpAddress(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $request->headers->set('Sec-GPC', '1');
        $requestStack = new RequestStack([$request]);

        $repository = $this->createMock(IpAddressRepository::class);
        $repository->expects($this->never())->method('saveEntity');
        $repository->expects($this->never())->method('__call');
        $this->deviceDetectorFactory->method('create')->willReturn($this->deviceDetector);

        $helper = new IpLookupHelper($requestStack, $repository, $this->createStub(CoreParametersHelper::class), $this->deviceDetectorFactory);
        $helper->reset();

        $this->assertTrue($helper->isRequestCountable());
    }

    public function testIsRequestCountableWithoutRequest(): void
    {
        $this->assertFalse($this->getIpHelper()->isRequestCountable());
    }

    private function getIpHelper(?Request $request = null, ?CoreParametersHelper $mockCoreParametersHelper = null): IpLookupHelper
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $mockRepository = $this->createMock(IpAddressRepository::class);
        $mockRepository
            ->method('__call')
            ->with('findOneByIpAddress')
            ->willReturn(null);

        if (null === $mockCoreParametersHelper) {
            $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
            $mockCoreParametersHelper
                ->method('get')
                ->willReturn(null);
        }

        $this->deviceDetectorFactory
            ->method('create')
            ->willReturnCallback(
                fn (): \PHPUnit\Framework\MockObject\MockObject => $this->deviceDetector
            );

        $helper = new IpLookupHelper($requestStack, $mockRepository, $mockCoreParametersHelper, $this->deviceDetectorFactory);
        $helper->reset();

        return $helper;
    }
}
