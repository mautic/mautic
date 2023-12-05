<?php

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\Stat;

class NotificationClickEvent extends CommonEvent
{
    private $request;

    private \Mautic\NotificationBundle\Entity\Notification $notification;

    public function __construct(Stat $stat, $request)
    {
        $this->entity       = $stat;
        $this->notification = $stat->getNotification();
        $this->request      = $request;
    }

    /**
     * Returns the Notification entity.
     *
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Get notification request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->entity;
    }
}
