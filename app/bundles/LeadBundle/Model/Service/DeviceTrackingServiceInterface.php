<?php

namespace Mautic\LeadBundle\Model\Service;

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
     * @param DeviceDetector $deviceDetector
     * @param bool           $replaceExisting
     * @param Lead|null      $assignedLead
     *
     * @return LeadDevice
     */
    public function trackCurrent(DeviceDetector $deviceDetector, $replaceExisting = false, Lead $assignedLead = null);
}
