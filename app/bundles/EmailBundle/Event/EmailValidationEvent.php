<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class EmailValidationEvent.
 */
class EmailValidationEvent extends Event
{
    /**
     * @var string
     */
    protected $address;

    /**
     * @var bool
     */
    protected $isValid = true;

    /**
     * @var string|null
     */
    protected $invalidReason;

    /**
     * EmailValidationEvent constructor.
     *
     * @param $address
     */
    public function __construct($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param $reason
     */
    public function setInvalid($reason)
    {
        $this->isValid       = false;
        $this->invalidReason = $reason;

        $this->stopPropagation();
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @return string|null
     */
    public function getInvalidReason()
    {
        return $this->invalidReason;
    }
}
