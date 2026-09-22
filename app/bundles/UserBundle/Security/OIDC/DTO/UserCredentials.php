<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\DTO;

final readonly class UserCredentials
{
    public function __construct(private string $id, private ?string $email = null, private ?string $preferredUsername = null, private ?string $givenName = null, private ?string $familyName = null)
    {
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
