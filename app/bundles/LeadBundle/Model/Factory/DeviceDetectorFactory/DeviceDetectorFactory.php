<?php

namespace Mautic\LeadBundle\Model\Factory\DeviceDetectorFactory;

use DeviceDetector\DeviceDetector;

/**
 * Class DeviceDetectorFactory.
 */
final class DeviceDetectorFactory implements DeviceDetectorFactoryInterface
{
    /**
     * @param string $userAgent
     *
     * @return DeviceDetector
     */
    public function create($userAgent)
    {
        return new DeviceDetector($userAgent);
    }
}
