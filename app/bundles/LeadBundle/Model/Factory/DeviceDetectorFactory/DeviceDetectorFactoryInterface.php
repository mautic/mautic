<?php

namespace Mautic\LeadBundle\Model\Factory\DeviceDetectorFactory;

use DeviceDetector\DeviceDetector;

/**
 * Interface DeviceDetectorFactoryInterface.
 */
interface DeviceDetectorFactoryInterface
{
    /**
     * @param string $userAgent
     *
     * @return DeviceDetector
     */
    public function create($userAgent);
}
