<?php

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

class ReplyEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    private ?\Symfony\Component\HttpFoundation\Response $response = null;

    /**
     * @var ?LeadEventLog
     */
    private $eventLog;

    /**
     * ReplyEvent constructor.
     *
     * @param string $message
     */
    public function __construct(
        private Lead $contact,
        private $message
    ) {
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function getEventLog(): ?LeadEventLog
    {
        return $this->eventLog;
    }

    public function setEventLog(LeadEventLog $eventLog): void
    {
        $this->eventLog = $eventLog;
    }
}
