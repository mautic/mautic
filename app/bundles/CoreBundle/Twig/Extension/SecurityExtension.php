<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\SecurityHelper;
use Mautic\UserBundle\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SecurityExtension extends AbstractExtension
{
    public function __construct(
        private readonly SecurityHelper $securityHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('securityGetAuthenticationContext', $this->getContext(...)),
            new TwigFunction('securityGetCsrfToken', $this->getCsrfToken(...)),
            new TwigFunction('securityHasEntityAccess', $this->hasEntityAccess(...)),
            new TwigFunction('securityIsGranted', $this->isGranted(...)),
        ];
    }

    public function getContext(): string
    {
        return $this->securityHelper->getAuthenticationContent();
    }

    public function getCsrfToken(string $intention): string
    {
        return $this->securityHelper->getCsrfToken($intention);
    }

    /**
     * Helper function to check if the logged in user has access to an entity.
     *
     * @param User|int $ownerId
     */
    public function hasEntityAccess(string|bool $ownPermission, string|bool $otherPermission, $ownerId, string|bool|null $sameRolePermission = null): bool
    {
        return $this->securityHelper->hasEntityAccess($ownPermission, $otherPermission, $ownerId, $sameRolePermission);
    }

    public function isGranted(string $permission): bool
    {
        return $this->securityHelper->isGranted($permission);
    }
}
