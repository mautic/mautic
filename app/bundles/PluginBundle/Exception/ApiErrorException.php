<?php

namespace Mautic\PluginBundle\Exception;

use Mautic\LeadBundle\Entity\Lead;

class ApiErrorException extends \Exception
{
    private $contactId;

    private ?Lead $contact = null;

    private string $shortMessage = '';

    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message = 'API error', $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @param mixed $contactId
     */
    public function setContactId($contactId): static
    {
        $this->contactId = $contactId;

        return $this;
    }

    public function getContact(): ?Lead
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getShortMessage(): string
    {
        return $this->shortMessage;
    }

    public function setShortMessage(string $shortMessage): ApiErrorException
    {
        $this->shortMessage = $shortMessage;

        return $this;
    }
}
