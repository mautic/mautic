<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC;

final class RegisterScopesEvent
{
    /**
     * @var string[]
     */
    public array $scopes = [];

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string[] $scopes
     */
    public function addScopes(array $scopes): void
    {
        $this->scopes = array_merge($this->scopes, $scopes);
    }
}
