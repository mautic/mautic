<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class EmailValidationEvent.
 */
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
     * EmailValidationEvent constructor.
     *
     * @param string $address
     */
    public function __construct(protected $address)
    {
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

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

    public function getInvalidReason(): ?string
    {
        return $this->invalidReason;
    }
}
