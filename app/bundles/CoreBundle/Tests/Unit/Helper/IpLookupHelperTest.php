<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use DeviceDetector\DeviceDetector;
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

    #[TestDox('Check that a bot request is not trackable and its IP address is not saved')]
    public function testIsRequestTrackableDoesNotSaveBotIpAddress(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);

        $this->deviceDetector
            ->method('isBot')
            ->willReturn(true);

        $repository = $this->createMock(IpAddressRepository::class);
        $repository->expects($this->never())->method('saveEntity');

        $this->assertFalse($this->getIpHelper($request, null, $repository)->isRequestTrackable());
    }

    #[TestDox('Check that a request from an IP on the do-not-track list is not trackable and the IP address is not saved')]
    public function testIsRequestTrackableDoesNotSaveDoNotTrackIpAddress(): void
    {
        $request                  = new Request([], [], [], [], [], ['REMOTE_ADDR' => '73.77.245.52']);
        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper
            ->method('get')
            ->willReturnCallback(
                fn ($param): ?array => 'do_not_track_ips' === $param ? ['73.77.245.52'] : null
            );

        $repository = $this->createMock(IpAddressRepository::class);
        $repository->expects($this->never())->method('saveEntity');

        $this->assertFalse($this->getIpHelper($request, $mockCoreParametersHelper, $repository)->isRequestTrackable());
    }

    public function testVisitorIsUntrackedOnlyOnceMarked(): void
    {
        $helper = $this->getIpHelper(new Request());
        $this->assertFalse($helper->isVisitorUntracked());

        $helper->markVisitorAsUntracked();
        $this->assertTrue($helper->isVisitorUntracked());

        // Without a request there is no visitor to mark
        $helper = $this->getIpHelper();
        $helper->markVisitorAsUntracked();
        $this->assertFalse($helper->isVisitorUntracked());
    }

    private function getIpHelper(?Request $request = null, ?CoreParametersHelper $mockCoreParametersHelper = null, ?IpAddressRepository $mockRepository = null): IpLookupHelper
    {
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
                fn (): \PHPUnit\Framework\MockObject\MockObject => $this->deviceDetector
            );

        $helper = new IpLookupHelper($requestStack, $mockRepository, $mockCoreParametersHelper, $this->deviceDetectorFactory);
        $helper->reset();

        return $helper;
    }
}
