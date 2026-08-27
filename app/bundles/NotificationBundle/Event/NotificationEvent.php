<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\NotificationBundle\Entity\Notification;

final class NotificationEvent extends CommonEvent
{
    public function __construct(Notification $notification, bool $isNew = false)
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
     */
    public function setNotification(Notification $notification): void
    {
        $this->entity = $notification;
    }
}
