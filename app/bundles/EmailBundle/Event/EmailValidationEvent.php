<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EmailValidationEvent extends Event
{
    /**
     * @var bool
     */
    protected $isValid = true;

    /**
     * @var string|null
     */
    protected $invalidReason;

    /**
     * @param string $address
     */
    public function __construct(
        protected $address
    ) {
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function setInvalid($reason): void
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
