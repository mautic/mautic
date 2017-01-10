<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\NotificationBundle\Entity\Notification;

/**
 * Class NotificationEvent.
 */
class NotificationEvent extends CommonEvent
{
    /**
     * @param Notification $notification
     * @param bool         $isNew
     */
    public function __construct(Notification $notification, $isNew = false)
    {
        $this->entity = $notification;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Notification entity.
     *
     * @return Notification
     */
    public function getNotification()
    {
        return $this->entity;
    }

    /**
     * Sets the Notification entity.
     *
     * @param Notification $notification
     */
    public function setNotification(Notification $notification)
    {
        $this->entity = $notification;
    }
}
