<?php

namespace Mautic\LeadBundle\Model\Service\DeviceCreatorService;

use DeviceDetector\DeviceDetector;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;

/**
 * Interface DeviceCreatorServiceInterface.
 */
interface DeviceCreatorServiceInterface
{
    /**
     * @param DeviceDetector $deviceDetector
     * @param Lead|null      $assignedLead
     *
     * @return LeadDevice|null Null is returned if device can't be detected
     */
    public function getCurrentFromDetector(DeviceDetector $deviceDetector, Lead $assignedLead = null);
}
