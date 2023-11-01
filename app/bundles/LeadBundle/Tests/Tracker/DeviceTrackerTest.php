<?php

namespace Mautic\LeadBundle\Tests\Tracker;

use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Monolog\Logger;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DeviceTrackerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DeviceCreatorService
     */
    private $deviceCreatorService;

    /**
     * @var DeviceDetectorFactory
     */
    private $deviceDetectorFactory;

    /**
     * @var DeviceTrackingServiceInterface
     */
    private $deviceTrackingService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36';

    protected function setUp(): void
    {
        $createCacheItem = \Closure::bind(
          function ($key) {
              $item = new CacheItem();
              $item->key = $key;
              $item->isHit = false;

              return $item;
          },
          $this,
          CacheItem::class
        );

        $cacheAdapter = $this->createMock(TagAwareAdapterInterface::class);
        $cacheAdapter->expects($this->atLeastOnce())
          ->method('getItem')
          ->withAnyParameters()
          ->willReturn($createCacheItem('test'));
        $cacheAdapter->expects($this->atLeastOnce())
          ->method('save')
          ->willReturn(true);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->expects($this->once())
          ->method('get')
          ->with($this->equalTo('cache_adapter'))
          ->willReturn('mautic.cache.adapter.filesystem');

        $container = $this->createMock(ContainerInterface::class);
        $container
          ->expects($this->once())
          ->method('has')
          ->with($this->equalTo('mautic.cache.adapter.filesystem'))
          ->willReturn(true);
        $container
          ->expects($this->once())
          ->method('get')
          ->with($this->equalTo('mautic.cache.adapter.filesystem'))
          ->willReturn($cacheAdapter);

        $cacheProvider               = new CacheProvider($coreParametersHelper, $container);
        $this->deviceDetectorFactory = new DeviceDetectorFactory($cacheProvider);
        $this->deviceCreatorService  = new DeviceCreatorService();
        $this->deviceTrackingService = $this->getMockBuilder(DeviceTrackingServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDeviceCreatedByUserAgent()
    {
        $lead    = new Lead();
        $device  = new LeadDevice();
        $device->setDeviceBrand('apple');

        $this->deviceTrackingService->expects($this->once())
            ->method('trackCurrentDevice')
            ->willReturn($device);

        $tracker = new DeviceTracker($this->deviceCreatorService, $this->deviceDetectorFactory, $this->deviceTrackingService, $this->logger);

        $device = $tracker->createDeviceFromUserAgent($lead, $this->userAgent);
        $this->assertEquals('3dfc9e6dff07948058df37455718cb98', $device->getSignature());

        // Subsequent calls should not create a new tracking ID
        $device2 = $tracker->createDeviceFromUserAgent($lead, $this->userAgent);
        $this->assertEquals($device->getTrackingId(), $device2->getTrackingId());
        $this->assertEquals('apple', $device2->getDeviceBrand());
        $this->assertEquals($device->getSignature(), $device2->getSignature());
    }
}
