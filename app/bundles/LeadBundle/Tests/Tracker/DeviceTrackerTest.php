<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Tracker;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Monolog\Logger;

class DeviceTrackerTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
    {
        $this->deviceCreatorService = new DeviceCreatorService();

        $this->deviceDetectorFactory = new DeviceDetectorFactory();

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
        $tracker = new DeviceTracker($this->deviceCreatorService, $this->deviceDetectorFactory, $this->deviceTrackingService, $this->logger);

        $device = $tracker->createDeviceFromUserAgent($lead, $this->userAgent);
        $this->assertEquals('d732b8950068a4a5908152c0eb049be5', $device->getSignature());

        // Subsequent calls should not create a new tracking ID
        $device2 = $tracker->createDeviceFromUserAgent($lead, $this->userAgent);
        $this->assertEquals($device->getTrackingId(), $device2->getTrackingId());
        $this->assertEquals($device->getSignature(), $device2->getSignature());
    }
}
