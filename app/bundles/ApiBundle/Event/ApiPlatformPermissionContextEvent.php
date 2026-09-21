<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use Symfony\Component\HttpFoundation\Request;

final class ApiPlatformPermissionContextEvent
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $securityExpression,
        private string $permission,
        private mixed $requestObject,
        private readonly ?Request $request = null,
        private readonly array $attributes = [],
    ) {
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getSecurityExpression(): string
    {
        return $this->securityExpression;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }

    public function getRequestObject(): mixed
    {
        return $this->requestObject;
    }

    public function setRequestObject(mixed $requestObject): void
    {
        $this->requestObject = $requestObject;
    }
}
