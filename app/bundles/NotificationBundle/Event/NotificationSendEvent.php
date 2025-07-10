<?php

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class NotificationSendEvent extends CommonEvent
{
    /**
     * @param string $message
     */
    public function __construct(
        protected $message,
        protected $heading,
        protected Lead $lead,
    ) {
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param mixed $heading
     *
     * @return NotificationSendEvent
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
