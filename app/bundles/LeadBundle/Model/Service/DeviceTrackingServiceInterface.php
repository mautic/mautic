<?php

namespace Mautic\LeadBundle\Model\Service;

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
     * @param bool      $replaceExisting
     * @param Lead|null $assignedLead
     *
     * @return LeadDevice|null Returns null if tracking is not possible at current request
     */
    public function trackCurrent($replaceExisting = false, Lead $assignedLead = null);
}
