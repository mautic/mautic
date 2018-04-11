<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Tracker\Service\DeviceTrackingService;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\RandomHelper\RandomHelperInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CompanyModelTest.
 */
final class DeviceTrackingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cookieHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $randomHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $leadDeviceRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStackMock;

    protected function setUp()
    {
        $this->cookieHelperMock            = $this->createMock(CookieHelper::class);
        $this->entityManagerMock           = $this->createMock(EntityManagerInterface::class);
        $this->randomHelperMock            = $this->createMock(RandomHelperInterface::class);
        $this->leadDeviceRepositoryMock    = $this->createMock(LeadDeviceRepository::class);
        $this->requestStackMock            = $this->createMock(RequestStack::class);
    }

    public function testIsTrackedTrue()
    {
        // Parameters
        $trackingId = 'randomTrackingId';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);
        $leadDeviceMock = $this->createMock(LeadDevice::class);

        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertTrue($deviceTrackingService->isTracked());
    }

    public function testIsTrackedFalse()
    {
        // Parameters
        $trackingId = 'randomTrackingId';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn(null);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertFalse($deviceTrackingService->isTracked());
    }

    public function testGetTrackedDeviceCookie()
    {
        // Parameters
        $trackingId = 'randomTrackingId';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $leadDeviceMock = $this->createMock(LeadDevice::class);
        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertSame($leadDeviceMock, $deviceTrackingService->getTrackedDevice());
    }

    public function testGetTrackedDeviceGetFromRequest()
    {
        // Parameters
        $trackingId = 'randomTrackingId';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);
        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $leadDeviceMock = $this->createMock(LeadDevice::class);
        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertSame($leadDeviceMock, $deviceTrackingService->getTrackedDevice());
    }

    public function testGetTrackedDeviceNoTrackingId()
    {
        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);
        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $this->leadDeviceRepositoryMock->expects($this->never())
            ->method('getByTrackingId');

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertNull($deviceTrackingService->getTrackedDevice());
    }

    public function testGetTrackedDeviceNoRequest()
    {
        // __construct()
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn(null);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertNull($deviceTrackingService->getTrackedDevice());
    }

    /**
     * Test tracking device with already tracked current device.
     */
    public function testTrackCurrentDeviceAlreadyTracked()
    {
        // Parameters
        $leadDeviceMock        = $this->createMock(LeadDevice::class);
        $trackingId            = 'randomTrackingId';
        $trackedLeadDeviceMock = $this->createMock(LeadDevice::class);

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedDevice()
        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($trackedLeadDeviceMock);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $returnedLeadDevice    = $deviceTrackingService->trackCurrentDevice($leadDeviceMock, false);
        $this->assertInstanceOf(LeadDevice::class, $returnedLeadDevice);
    }

    /**
     * Test tracking device with already tracked current device, replace existing tracking.
     */
    public function testTrackCurrentDeviceAlreadyTrackedReplaceExistingTracking()
    {
        // Parameters
        $leadDeviceMock           = $this->createMock(LeadDevice::class);
        $trackingId               = 'randomTrackingId';
        $trackedLeadDeviceMock    = $this->createMock(LeadDevice::class);
        $uniqueTrackingIdentifier = '1234567890abcdefghij123';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedDevice()
        // getTrackedIdentifier()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->leadDeviceRepositoryMock->expects($this->at(0))
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($trackedLeadDeviceMock);

        // getUniqueTrackingIdentifier()
        $this->randomHelperMock->expects($this->at(0))
            ->method('generate')
            ->with(23)
            ->willReturn($uniqueTrackingIdentifier);

        $this->entityManagerMock->expects($this->at(0))
            ->method('persist')
            ->with($leadDeviceMock);

        // index 0-3 for leadDeviceRepository::findOneBy
        $leadDeviceMock->expects($this->at(4))
            ->method('getTrackingId')
            ->willReturn(null);
        $leadDeviceMock->expects($this->at(5))
            ->method('setTrackingId')
            ->with($uniqueTrackingIdentifier)
            ->willReturn($leadDeviceMock);
        $leadDeviceMock->expects($this->at(6))
            ->method('getTrackingId')
            ->willReturn($uniqueTrackingIdentifier);
        $leadDeviceMock->expects($this->exactly(3))
            ->method('getLead')
            ->willReturn(new Lead());
        $this->cookieHelperMock->expects($this->at(2))
            ->method('setCookie')
            ->with('mautic_device_id', $uniqueTrackingIdentifier, 31536000);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $returnedLeadDevice    = $deviceTrackingService->trackCurrentDevice($leadDeviceMock, true);
        $this->assertInstanceOf(LeadDevice::class, $returnedLeadDevice);
    }

    /**
     * Test tracking device without already tracked current device.
     */
    public function testTrackCurrentDeviceNotTrackedYet()
    {
        // Parameters
        $leadDeviceMock           = $this->createMock(LeadDevice::class);
        $uniqueTrackingIdentifier = '1234567890abcdefghij123';

        // __construct()
        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->at(0))
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        // getTrackedDevice()
        $this->cookieHelperMock->expects($this->at(0))
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $requestMock->expects($this->at(0))
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $leadDeviceMock->expects($this->at(0))
            ->method('getTrackingId')
            ->willReturn(null);

        // getUniqueTrackingIdentifier()
        $this->randomHelperMock->expects($this->at(0))
            ->method('generate')
            ->with(23)
            ->willReturn($uniqueTrackingIdentifier);

        // index 0-3 for leadDeviceRepository::findOneBy
        $leadDeviceMock->expects($this->at(4))
            ->method('getTrackingId')
            ->willReturn(null);
        $leadDeviceMock->expects($this->at(5))
            ->method('setTrackingId')
            ->with($uniqueTrackingIdentifier)
            ->willReturn($leadDeviceMock);
        $leadDeviceMock->expects($this->at(6))
            ->method('getTrackingId')
            ->willReturn($uniqueTrackingIdentifier);
        $leadDeviceMock->expects($this->exactly(3))
            ->method('getLead')
            ->willReturn(new Lead());

        $this->cookieHelperMock->expects($this->at(2))
            ->method('setCookie')
            ->with('mautic_device_id', $uniqueTrackingIdentifier, 31536000);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $returnedLeadDevice    = $deviceTrackingService->trackCurrentDevice($leadDeviceMock, false);
        $this->assertInstanceOf(LeadDevice::class, $returnedLeadDevice);
    }

    /**
     * @return DeviceTrackingService
     */
    private function getDeviceTrackingService()
    {
        return new DeviceTrackingService(
            $this->cookieHelperMock,
            $this->entityManagerMock,
            $this->leadDeviceRepositoryMock,
            $this->randomHelperMock,
            $this->requestStackMock
        );
    }
}
