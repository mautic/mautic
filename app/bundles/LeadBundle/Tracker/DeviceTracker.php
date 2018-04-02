<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tracker;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorServiceInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Monolog\Logger;

class DeviceTracker
{
    /**
     * @var DeviceCreatorServiceInterface
     */
    private $deviceCreatorService;

    /**
     * @var DeviceDetectorFactoryInterface
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

    public function __construct(
        DeviceCreatorServiceInterface $deviceCreatorService,
        DeviceDetectorFactoryInterface $deviceDetectorFactory,
        DeviceTrackingServiceInterface $deviceTrackingService,
        Logger $logger
    ) {
        $this->deviceCreatorService  = $deviceCreatorService;
        $this->deviceDetectorFactory = $deviceDetectorFactory;
        $this->deviceTrackingService = $deviceTrackingService;
        $this->logger                = $logger;
    }

    /**
     * @param Lead $trackedContact
     * @param      $userAgent
     *
     * @return \Mautic\LeadBundle\Entity\LeadDevice|null
     */
    public function createDeviceFromUserAgent(Lead $trackedContact, $userAgent)
    {
        $deviceDetector = $this->deviceDetectorFactory->create($userAgent);
        $deviceDetector->parse();
        $currentDevice = $this->deviceCreatorService->getCurrentFromDetector($deviceDetector, $trackedContact);
        $this->deviceTrackingService->trackCurrentDevice($currentDevice, false);

        return $currentDevice;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\LeadDevice|null
     */
    public function getTrackedDevice()
    {
        $trackedDevice = $this->deviceTrackingService->getTrackedDevice();

        if ($trackedDevice !== null) {
            $this->logger->addDebug("LEAD: Tracking ID for this device is {$trackedDevice->getTrackingId()}");
        }

        return $trackedDevice;
    }
}
