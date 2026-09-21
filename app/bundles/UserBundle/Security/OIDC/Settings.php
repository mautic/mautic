<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Mautic\UserBundle\Entity\Role;

final class Settings
{
    private bool $isEnabled;
    private bool $isRequired;
    private bool $isUserRegistrationAllowed;
    private ?Role $registeredUserRole;

    public function __construct(
        bool $isEnabled,
        bool $isRequired,
        bool $isUserRegistrationAllowed,
        ?Role $registeredUserRole = null,
    ) {
        $this->isEnabled                   = $isEnabled;
        $this->isRequired                  = $isRequired;
        $this->isUserRegistrationAllowed   = $isUserRegistrationAllowed;
        $this->registeredUserRole          = $registeredUserRole;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isUserRegistrationAllowed(): bool
    {
        return $this->isUserRegistrationAllowed;
    }

    public function getRegisteredUserRole(): ?Role
    {
        return $this->registeredUserRole;
    }
}
