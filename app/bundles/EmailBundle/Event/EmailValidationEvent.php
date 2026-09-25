<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class EmailValidationEvent extends Event
{
    private bool $isValid = true;

    private ?string $invalidReason = null;

    /**
     * @param string $address
     */
    public function __construct(
        private $address,
    ) {
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function setInvalid(string $reason): void
    {
        $this->isValid       = false;
        $this->invalidReason = $reason;

        $this->stopPropagation();
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getInvalidReason(): ?string
    {
        return $this->invalidReason;
    }
}
