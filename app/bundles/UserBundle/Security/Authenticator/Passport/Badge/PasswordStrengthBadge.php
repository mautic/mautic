<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class PasswordStrengthBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(private ?string $presentedPassword)
    {
    }

    public function getPresentedPassword(): ?string
    {
        return $this->presentedPassword;
    }

    public function setResolved(): void
    {
        $this->resolved = true;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
