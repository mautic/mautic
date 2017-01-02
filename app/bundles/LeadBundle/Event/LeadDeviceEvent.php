<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\LeadDevice;

/**
 * Class LeadDeviceEvent.
 */
class LeadDeviceEvent extends CommonEvent
{
    /**
     * @param LeadDevice $device
     * @param bool       $isNew
     */
    public function __construct(LeadDevice &$device, $isNew = false)
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
     *
     * @param LeadDevice $device
     */
    public function setDevice(LeadDevice $device)
    {
        $this->entity = $device;
    }
}
