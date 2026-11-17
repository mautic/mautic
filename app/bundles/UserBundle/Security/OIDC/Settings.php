<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class Settings
{
    public function __construct(
        #[Autowire(param: 'mautic.open_id_is_enabled')]
        private bool $isEnabled,
        #[Autowire(param: 'mautic.open_id_is_required')]
        private bool $isRequired,
        #[Autowire(param: 'mautic.open_id_is_user_registration_allowed')]
        private bool $isUserRegistrationAllowed,
        #[Autowire(param: 'mautic.open_id_registered_user_role')]
        private ?int $registeredUserRoleId = null,
    ) {
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

    public function getRegisteredUserRoleId(): ?int
    {
        return $this->registeredUserRoleId;
    }
}
