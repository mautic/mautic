<?php

declare(strict_types=1);

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
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class DeviceTrackingServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject
     */
    private $cookieHelperMock;

    /**
     * @var MockObject
     */
    private $entityManagerMock;

    /**
     * @var MockObject
     */
    private $randomHelperMock;

    /**
     * @var MockObject
     */
    private $leadDeviceRepositoryMock;

    /**
     * @var MockObject
     */
    private $requestStackMock;

    /**
     * @var MockObject
     */
    private $security;

    protected function setUp(): void
    {
        $this->cookieHelperMock            = $this->createMock(CookieHelper::class);
        $this->entityManagerMock           = $this->createMock(EntityManagerInterface::class);
        $this->randomHelperMock            = $this->createMock(RandomHelperInterface::class);
        $this->leadDeviceRepositoryMock    = $this->createMock(LeadDeviceRepository::class);
        $this->requestStackMock            = $this->createMock(RequestStack::class);
        $this->security                    = $this->createMock(CorePermissions::class);
    }

    public function testIsTrackedTrue(): void
    {
        $trackingId  = 'randomTrackingId';
        $requestMock = $this->createMock(Request::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);
        $leadDeviceMock = $this->createMock(LeadDevice::class);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->once())
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $this->assertTrue($this->getDeviceTrackingService()->isTracked());
    }

    public function testIsTrackedFalse(): void
    {
        $trackingId  = 'randomTrackingId';
        $requestMock = $this->createMock(Request::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->once())
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn(null);

        $this->assertFalse($this->getDeviceTrackingService()->isTracked());
    }

    public function testGetTrackedDeviceCookie(): void
    {
        $trackingId     = 'randomTrackingId';
        $leadDeviceMock = $this->createMock(LeadDevice::class);
        $requestMock    = $this->createMock(Request::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->once())
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $this->assertSame($leadDeviceMock, $this->getDeviceTrackingService()->getTrackedDevice());
    }

    public function testGetTrackedDeviceGetFromRequest(): void
    {
        $trackingId     = 'randomTrackingId';
        $requestMock    = $this->createMock(Request::class);
        $leadDeviceMock = $this->createMock(LeadDevice::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $requestMock->expects($this->once())
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->once())
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($leadDeviceMock);

        $this->assertSame($leadDeviceMock, $this->getDeviceTrackingService()->getTrackedDevice());
    }

    public function testGetTrackedDeviceNoTrackingId(): void
    {
        $requestMock = $this->createMock(Request::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $requestMock->expects($this->once())
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->never())
            ->method('getByTrackingId');

        $this->assertNull($this->getDeviceTrackingService()->getTrackedDevice());
    }

    public function testGetTrackedDeviceNoRequest()
    {
        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertNull($deviceTrackingService->getTrackedDevice());
    }

    /**
     * Test tracking device with already tracked current device.
     */
    public function testTrackCurrentDeviceAlreadyTracked(): void
    {
        $leadDeviceMock        = $this->createMock(LeadDevice::class);
        $trackingId            = 'randomTrackingId';
        $trackedLeadDeviceMock = $this->createMock(LeadDevice::class);

        $requestMock = $this->createMock(Request::class);
        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->expects($this->once())
            ->method('getByTrackingId')
            ->with($trackingId)
            ->willReturn($trackedLeadDeviceMock);

        $deviceTrackingService = $this->getDeviceTrackingService();

        $this->assertInstanceOf(LeadDevice::class, $deviceTrackingService->trackCurrentDevice($leadDeviceMock, false));
    }

    /**
     * Test tracking device with already tracked current device, replace existing tracking.
     */
    public function testTrackCurrentDeviceAlreadyTrackedReplaceExistingTracking(): void
    {
        $leadDeviceMock           = $this->createMock(LeadDevice::class);
        $trackedLeadDeviceMock    = $this->createMock(LeadDevice::class);
        $requestMock              = $this->createMock(Request::class);
        $trackingId               = 'randomTrackingId';
        $uniqueTrackingIdentifier = '1234567890abcdefghij123';

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn($trackingId);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadDeviceRepositoryMock->method('getByTrackingId')
            ->withConsecutive([$trackingId])
            ->willReturnOnConsecutiveCalls($trackedLeadDeviceMock);

        $this->randomHelperMock->expects($this->once())
            ->method('generate')
            ->with(23)
            ->willReturn($uniqueTrackingIdentifier);

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($leadDeviceMock);

        // index 0-3 for leadDeviceRepository::findOneBy
        $leadDeviceMock->method('getTrackingId')
            ->willReturnOnConsecutiveCalls(null, $uniqueTrackingIdentifier);

        $leadDeviceMock->expects($this->once())
            ->method('setTrackingId')
            ->with($uniqueTrackingIdentifier)
            ->willReturn($leadDeviceMock);

        $leadDeviceMock->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn(new Lead());

        $this->cookieHelperMock->method('setCookie')
            ->withConsecutive(['mautic_device_id', $uniqueTrackingIdentifier, 31536000]);

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertInstanceOf(LeadDevice::class, $deviceTrackingService->trackCurrentDevice($leadDeviceMock, true));
    }

    /**
     * Test tracking device without already tracked current device.
     */
    public function testTrackCurrentDeviceNotTrackedYet(): void
    {
        $leadDeviceMock           = $this->createMock(LeadDevice::class);
        $uniqueTrackingIdentifier = '1234567890abcdefghij123';
        $requestMock              = $this->createMock(Request::class);

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);

        $this->cookieHelperMock->expects($this->once())
            ->method('getCookie')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $requestMock->expects($this->once())
            ->method('get')
            ->with('mautic_device_id', null)
            ->willReturn(null);

        $this->randomHelperMock->expects($this->once())
            ->method('generate')
            ->with(23)
            ->willReturn($uniqueTrackingIdentifier);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        // index 0-3 for leadDeviceRepository::findOneBy
        $leadDeviceMock->method('getTrackingId')
            ->willReturnOnConsecutiveCalls(null, $uniqueTrackingIdentifier);

        $leadDeviceMock->expects($this->once())
            ->method('setTrackingId')
            ->with($uniqueTrackingIdentifier)
            ->willReturn($leadDeviceMock);

        $leadDeviceMock->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn(new Lead());

        $this->cookieHelperMock->method('setCookie')
            ->withConsecutive(['mautic_device_id', $uniqueTrackingIdentifier, 31536000]);

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($leadDeviceMock);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $deviceTrackingService = $this->getDeviceTrackingService();
        $this->assertInstanceOf(LeadDevice::class, $deviceTrackingService->trackCurrentDevice($leadDeviceMock, false));
    }

    /**
     * Test that a user is not tracked.
     */
    public function testUserIsNotTracked(): void
    {
        $this->leadDeviceRepositoryMock->expects($this->never())
            ->method('getByTrackingId');

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(false);

        $this->getDeviceTrackingService()->getTrackedDevice();
    }

    private function getDeviceTrackingService(): DeviceTrackingService
    {
        return new DeviceTrackingService(
            $this->cookieHelperMock,
            $this->entityManagerMock,
            $this->leadDeviceRepositoryMock,
            $this->randomHelperMock,
            $this->requestStackMock,
            $this->security
        );
    }
}
