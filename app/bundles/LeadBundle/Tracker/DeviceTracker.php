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
use Mautic\LeadBundle\Entity\LeadDevice;
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

    /**
     * @var bool
     */
    private $deviceWasChanged = false;

    /**
     * @var LeadDevice[]
     */
    private $trackedDevice = [];

    /**
     * DeviceTracker constructor.
     *
     * @param DeviceCreatorServiceInterface  $deviceCreatorService
     * @param DeviceDetectorFactoryInterface $deviceDetectorFactory
     * @param DeviceTrackingServiceInterface $deviceTrackingService
     * @param Logger                         $logger
     */
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
        $signature = $trackedContact->getId().$userAgent;
        if (isset($this->trackedDevice[$signature])) {
            // Prevent subsequent calls within the same session from creating multiple entries
            return $this->trackedDevice[$signature];
        }

        $this->trackedDevice[$signature] = $trackedDevice = $this->deviceTrackingService->getTrackedDevice();

        $deviceDetector = $this->deviceDetectorFactory->create($userAgent);
        $deviceDetector->parse();
        $currentDevice = $this->deviceCreatorService->getCurrentFromDetector($deviceDetector, $trackedContact);

        if ( // Do not create a new device if
            // ... the device is new
            $trackedDevice && $trackedDevice->getId() &&
            // ... the device is the same
            $trackedDevice->getSignature() === $currentDevice->getSignature() &&
            // ... the contact given is the same as the owner of the device tracked
            $trackedDevice->getLead()->getId() === $trackedContact->getId()
        ) {
            // Devices are the same so do not create a new entry
            return $trackedDevice;
        }

        // New device so record it and track it
        $this->trackedDevice[$signature] = $currentDevice;
        $this->deviceTrackingService->trackCurrentDevice($currentDevice, true);
        $this->deviceWasChanged = true;

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

    /**
     * @return bool
     */
    public function wasDeviceChanged()
    {
        return $this->deviceWasChanged;
    }
}
