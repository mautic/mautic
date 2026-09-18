<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\DTO;

final class UserCredentials
{
    private string $id;
    private ?string $email;
    private ?string $preferredUsername;
    private ?string $givenName;
    private ?string $familyName;

    public function __construct(
        string $id,
        ?string $email = null,
        ?string $preferredUsername = null,
        ?string $name = null,
        ?string $familyName = null,
    ) {
        $this->id                = $id;
        $this->email             = $email;
        $this->preferredUsername = $preferredUsername;
        $this->givenName         = $name;
        $this->familyName        = $familyName;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPreferredUsername(): ?string
    {
        return $this->preferredUsername;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }
}
