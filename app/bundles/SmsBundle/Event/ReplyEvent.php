<?php

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class ReplyEvent extends Event
{
    /**
     * @var Response|null
     */
    private $response;

    /**
     * ReplyEvent constructor.
     *
     * @param string $message
     */
    public function __construct(private Lead $contact, private $message)
    {
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

    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
