<?php

namespace Mautic\LeadBundle\Model\Service\DeviceTrackingService;

use DeviceDetector\DeviceDetector;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;

/**
 * Interface DeviceTrackingServiceInterface.
 */
interface DeviceTrackingServiceInterface
{
    /**
     * @return bool
     */
    public function isTracked();

    /**
     * @return LeadDevice|null
     */
    public function getTrackedDevice();

    /**
     * @param LeadDevice     $device
     * @param bool           $replaceExistingTracking
     *
     * @return LeadDevice
     */
    public function trackCurrentDevice(LeadDevice $device, $replaceExistingTracking = false);
}
