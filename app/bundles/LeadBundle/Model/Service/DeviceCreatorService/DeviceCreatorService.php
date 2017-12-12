<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model\Service\DeviceCreatorService;

use DeviceDetector\DeviceDetector;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;

/**
 * Class DeviceCreatorService.
 */
final class DeviceCreatorService implements DeviceCreatorServiceInterface
{
    /**
     * @param DeviceDetector $deviceDetector
     * @param Lead|null      $assignedLead
     *
     * @return LeadDevice|null Null is returned if device can't be detected
     */
    public function getCurrentFromDetector(DeviceDetector $deviceDetector, Lead $assignedLead = null)
    {
        if ($assignedLead !== null) {
            @trigger_error('Parameter $assignedLead is deprecated and will be removed in 3.0', E_USER_DEPRECATED);
        }
        $device = new LeadDevice();
        $device->setClientInfo($deviceDetector->getClient());
        $device->setDevice($deviceDetector->getDeviceName());
        $device->setDeviceBrand($deviceDetector->getBrand());
        $device->setDeviceModel($deviceDetector->getModel());
        $device->setDeviceOs($deviceDetector->getOs());
        $device->setDateAdded(new \DateTime());
        if ($assignedLead === null) {
            $assignedLead = new Lead();
        }
        $device->setLead($assignedLead);

        return $device;
    }
}
