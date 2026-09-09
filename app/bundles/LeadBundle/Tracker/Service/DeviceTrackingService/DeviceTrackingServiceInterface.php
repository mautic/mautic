<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tracker\Service\DeviceTrackingService;

use Mautic\LeadBundle\Entity\LeadDevice;

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
     * @return LeadDevice
     */
    public function trackCurrentDevice(LeadDevice $device, bool $replaceExistingTracking = false);

    public function clearTrackingCookies();

    /**
     * Resets cache.
     */
    public function reset(): void;
}
