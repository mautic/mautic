<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Factory;

use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Security\OIDC\Settings;

final readonly class SettingsFactory
{
    public function __construct(private RoleRepository $roleRepository)
    {
    }

    public function create(?bool $isEnabled, ?bool $isRequired, ?bool $isUserCreationAllowed, ?int $newUserRole): Settings
    {
        return new Settings(
            $isEnabled ?? false,
            $isRequired ?? false,
            $isUserCreationAllowed ?? false,
            $newUserRole ? $this->roleRepository->find($newUserRole) : null
        );
    }
}
