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
use Mautic\NotificationBundle\Entity\Stat;

/**
 * Class NotificationClickEvent.
 */
class NotificationClickEvent extends CommonEvent
{
    private $request;

    private $notification;

    /**
     * @param Stat $stat
     * @param $request
     */
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
