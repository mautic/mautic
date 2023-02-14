<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Factory;

use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\UserBundle\Entity\RoleRepository;

final class SettingsFactory
{
    private RoleRepository $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
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
