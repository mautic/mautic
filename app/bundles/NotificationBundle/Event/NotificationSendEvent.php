<?php

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

class NotificationSendEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $message;

    protected $heading;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @param string $message
     */
    public function __construct($message, $heading, Lead $lead)
    {
        $this->message = $message;
        $this->heading = $heading;
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
