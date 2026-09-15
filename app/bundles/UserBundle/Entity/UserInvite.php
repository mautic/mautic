<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: UserInviteRepository::class)]
#[ORM\Table(name: 'user_invites')]
#[ORM\Index(name: 'IDX_USER_INVITES_EMAIL', columns: ['email'])]
#[ORM\Index(name: 'IDX_USER_INVITES_EXPIRATION', columns: ['expiration'])]
#[ORM\Index(name: 'IDX_USER_INVITES_ROLE', columns: ['role_id'])]
#[ORM\Index(name: 'IDX_USER_INVITES_USED', columns: ['used'])]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_INVITES_TOKEN_SELECTOR', columns: ['token_selector'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class UserInvite
{
    private ?int $id = null;

    private ?string $email = null;

    private ?string $tokenSelector = null;

    private ?string $tokenVerifierHash = null;

    private ?\DateTimeInterface $expiration = null;

    private bool $used = false;

    public function __construct(
        private Role $role,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addId();

        $builder->createField('email', Types::STRING)
            ->length(191)
            ->build();

        $builder->createField('tokenSelector', Types::STRING)
            ->columnName('token_selector')
            ->length(32)
            ->build();

        $builder->createField('tokenVerifierHash', Types::STRING)
            ->columnName('token_verifier_hash')
            ->length(255)
            ->build();

        $builder->createField('expiration', Types::DATETIME_MUTABLE)
            ->build();

        $builder->createField('used', Types::BOOLEAN)
            ->build();

        $builder->createManyToOne('role', Role::class)
            ->addJoinColumn('role_id', 'id', false, false, 'CASCADE')
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTokenSelector(): ?string
    {
        return $this->tokenSelector;
    }

    public function setTokenSelector(string $tokenSelector): self
    {
        $this->tokenSelector = $tokenSelector;

        return $this;
    }

    public function getTokenVerifierHash(): ?string
    {
        return $this->tokenVerifierHash;
    }

    public function setTokenVerifierHash(string $tokenVerifierHash): self
    {
        $this->tokenVerifierHash = $tokenVerifierHash;

        return $this;
    }

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function setExpiration(\DateTimeInterface $expiration): self
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): self
    {
        $this->used = $used;

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
