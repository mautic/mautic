<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\LeadDevice;

final class LeadDeviceEvent extends CommonEvent
{
    public function __construct(LeadDevice &$device, bool $isNew = false)
    {
        $this->entity = &$device;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the LeadDevice entity.
     *
     * @return LeadDevice
     */
    public function getDevice()
    {
        return $this->entity;
    }

    /**
     * Sets the LeadDevice entity.
     */
    public function setDevice(LeadDevice $device): void
    {
        $this->entity = $device;
    }
}
