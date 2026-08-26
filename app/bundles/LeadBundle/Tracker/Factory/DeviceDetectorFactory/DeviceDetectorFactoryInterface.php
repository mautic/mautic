<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory;

use DeviceDetector\DeviceDetector;

interface DeviceDetectorFactoryInterface
{
    /**
     * @param string $userAgent
     *
     * @return DeviceDetector
     */
    public function create($userAgent);
}
