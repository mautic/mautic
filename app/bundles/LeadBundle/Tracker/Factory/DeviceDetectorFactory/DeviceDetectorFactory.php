<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory;

use DeviceDetector\DeviceDetector;

/**
 * Class DeviceDetectorFactory.
 */
class DeviceDetectorFactory implements DeviceDetectorFactoryInterface
{
    /**
     * @param string $userAgent
     *
     * @return DeviceDetector
     *
     * @throws \Exception
     */
    public function create($userAgent)
    {
        return new DeviceDetector($userAgent);
    }
}
