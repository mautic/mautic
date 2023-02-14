<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class RegisterScopesEvent extends Event
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

    public function addScope(string $scope): void
    {
        $this->scopes[] = $scope;
    }

    /**
     * @param string[] $scopes
     */
    public function addScopes(array $scopes): void
    {
        $this->scopes = array_merge($this->scopes, $scopes);
    }
}
