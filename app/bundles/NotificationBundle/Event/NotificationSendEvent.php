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
     */
    public function setHeading($heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }
}
