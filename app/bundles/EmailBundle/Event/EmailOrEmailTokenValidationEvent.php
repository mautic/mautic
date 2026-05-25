<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

class EmailOrEmailTokenValidationEvent
{
    private bool $isValid = false;
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): void
    {
        $this->isValid = $isValid;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
