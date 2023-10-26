<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class PasswordStrengthValidateEvent extends Event
{
    public function __construct(
        private bool $isValid,
        private string $password
    ) {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): PasswordStrengthValidateEvent
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): PasswordStrengthValidateEvent
    {
        $this->password = $password;

        return $this;
    }
}
