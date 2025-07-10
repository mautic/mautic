<?php

namespace Mautic\PluginBundle\Exception;

use Mautic\LeadBundle\Entity\Lead;

class ApiErrorException extends \Exception
{
    private $contactId;

    private ?Lead $contact = null;

    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message = 'API error', $code = 0, \Exception $previous = null)
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
     *
     * @return ApiErrorException
     */
    public function setContactId($contactId)
    {
        $this->contactId = $contactId;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return ApiErrorException
     */
    public function setContact(Lead $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
