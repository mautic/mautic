<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\SecurityHelper;
use Mautic\UserBundle\Entity\User;

class SecurityExtension
{
    public function __construct(
        private readonly SecurityHelper $securityHelper,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'securityGetAuthenticationContext')]
    public function getContext(): string
    {
        return $this->securityHelper->getAuthenticationContent();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'securityGetCsrfToken')]
    public function getCsrfToken(string $intention): string
    {
        return $this->securityHelper->getCsrfToken($intention);
    }

    /**
     * Helper function to check if the logged in user has access to an entity.
     *
     * @param string|bool $ownPermission
     * @param string|bool $otherPermission
     * @param User|int    $ownerId
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'securityHasEntityAccess')]
    public function hasEntityAccess($ownPermission, $otherPermission, $ownerId): bool
    {
        return $this->securityHelper->hasEntityAccess($ownPermission, $otherPermission, $ownerId);
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'securityIsGranted')]
    public function isGranted(string $permission): bool
    {
        return $this->securityHelper->isGranted($permission);
    }
}
