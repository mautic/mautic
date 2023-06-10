<?php

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class NotificationSendEvent extends CommonEvent
{
    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @param string $message
     */
    public function __construct(protected $message, protected $heading, Lead $lead)
    {
        $this->lead    = $lead;
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
    public function setMessage($message)
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
     * @return NotificationSendEvent
     */
    public function setHeading(mixed $heading)
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
