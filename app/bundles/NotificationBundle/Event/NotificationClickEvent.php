<?php

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Entity\Stat;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class NotificationClickEvent extends CommonEvent
{
    private \Mautic\NotificationBundle\Entity\Notification $notification;

    public function __construct(
        Stat $stat,
        private $request
    ) {
        $this->entity       = $stat;
        $this->notification = $stat->getNotification();
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
