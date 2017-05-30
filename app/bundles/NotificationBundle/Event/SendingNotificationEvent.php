<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Entity\Notification;

/**
 * Class SendingNotificationEvent.
 */
class SendingNotificationEvent extends CommonEvent
{
    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @var Notification
     */
    protected $entity;

    /**
     * SendingNotificationEvent constructor.
     *
     * @param Notification $notification
     * @param Lead         $lead
     */
    public function __construct(Notification $notification, Lead $lead)
    {
        $this->entity = $notification;
        $this->lead   = $lead;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->entity;
    }

    /**
     * @param Notification $notification
     *
     * @return $this
     */
    public function setNotifiction(Notification $notification)
    {
        $this->entity = $notification;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     *
     * @return $this
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
