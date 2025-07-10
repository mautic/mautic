<?php

namespace Mautic\LeadBundle\Tracker;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactoryInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorServiceInterface;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Psr\Log\LoggerInterface;

class DeviceTracker
{
    private bool $deviceWasChanged = false;

    /**
     * @var LeadDevice[]
     */
    private array $trackedDevice = [];

    public function __construct(
        private DeviceCreatorServiceInterface $deviceCreatorService,
        private DeviceDetectorFactoryInterface $deviceDetectorFactory,
        private DeviceTrackingServiceInterface $deviceTrackingService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return LeadDevice|null
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
            $trackedDevice && $trackedDevice->getId() // ... the device is the same
            && $trackedDevice->getSignature() === $currentDevice->getSignature() // ... the contact given is the same as the owner of the device tracked
            && $trackedDevice->getLead()->getId() === $trackedContact->getId()
        ) {
            return $trackedDevice;
        }

        // New device so record it and track it
        $this->deviceWasChanged = true;

        $this->trackedDevice[$signature] = $this->deviceTrackingService->trackCurrentDevice($currentDevice, true);

        return $this->trackedDevice[$signature];
    }

    /**
     * @return LeadDevice|null
     */
    public function getTrackedDevice()
    {
        $trackedDevice = $this->deviceTrackingService->getTrackedDevice();

        if (null !== $trackedDevice) {
            $this->logger->debug("LEAD: Tracking ID for this device is {$trackedDevice->getTrackingId()}");
        }

        return $trackedDevice;
    }

    public function wasDeviceChanged(): bool
    {
        return $this->deviceWasChanged;
    }

    public function clearTrackingCookies(): void
    {
        $this->deviceTrackingService->clearTrackingCookies();
    }
}
