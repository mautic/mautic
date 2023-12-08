<?php

namespace Mautic\LeadBundle\Tracker\Service\DeviceCreatorService;

use DeviceDetector\DeviceDetector;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;

final class DeviceCreatorService implements DeviceCreatorServiceInterface
{
    public function getCurrentFromDetector(DeviceDetector $deviceDetector, Lead $assignedLead): LeadDevice
    {
        $device = new LeadDevice();
        $device->setClientInfo($deviceDetector->getClient());
        $device->setDevice($deviceDetector->getDeviceName());
        $device->setDeviceBrand($deviceDetector->getBrandName());
        $device->setDeviceModel($deviceDetector->getModel());
        $device->setDeviceOs($deviceDetector->getOs());
        $device->setDateAdded(new \DateTime());
        $device->setLead($assignedLead);

        return $device;
    }
}
