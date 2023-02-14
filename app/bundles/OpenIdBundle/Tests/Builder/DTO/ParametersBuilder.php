<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Builder\DTO;

use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\UserBundle\Entity\Role;

final class ParametersBuilder
{
    private bool $isEnabled                 = true;
    private bool $isRequired                = true;
    private bool $isUserRegistrationAllowed = true;
    private ?Role $registeredUserRole       = null;

    public function build(): Settings
    {
        return new Settings(
            $this->isEnabled,
            $this->isRequired,
            $this->isUserRegistrationAllowed,
            $this->registeredUserRole
        );
    }

    public function withIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function withIsRequired(bool $isRequired): self
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    public function withIsUserRegistrationAllowed(bool $isUserRegistrationAllowed): self
    {
        $this->isUserRegistrationAllowed = $isUserRegistrationAllowed;

        return $this;
    }

    public function withRegisteredUserRole(?Role $registeredUserRole): self
    {
        $this->registeredUserRole = $registeredUserRole;

        return $this;
    }
}
