<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator\Passport\Badge;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class PluginBadge implements BadgeInterface
{
    public function __construct(private ?TokenInterface $preAuthenticatedToken, private ?Response $pluginResponse, private ?string $authenticatingService)
    {
    }

    public function getPreAuthenticatedToken(): ?TokenInterface
    {
        return $this->preAuthenticatedToken;
    }

    public function getPluginResponse(): ?Response
    {
        return $this->pluginResponse;
    }

    public function getAuthenticatingService(): ?string
    {
        return $this->authenticatingService;
    }

    public function isResolved(): bool
    {
        return null !== $this->preAuthenticatedToken || null !== $this->pluginResponse;
    }
}
