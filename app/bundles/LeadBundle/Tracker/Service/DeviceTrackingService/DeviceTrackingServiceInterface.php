<?php

namespace Mautic\LeadBundle\Tracker\Service\DeviceTrackingService;

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

    public function trackCurrentDevice(LeadDevice $device, bool $replaceExistingTracking = false): LeadDevice;

    public function clearTrackingCookies();
}
