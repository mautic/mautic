<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: UserInviteRepository::class)]
#[ORM\Table(name: 'user_invites')]
#[ORM\Index(columns: ['email'], name: 'IDX_USER_INVITES_EMAIL')]
#[ORM\Index(columns: ['expiration'], name: 'IDX_USER_INVITES_EXPIRATION')]
#[ORM\Index(columns: ['role_id'], name: 'IDX_USER_INVITES_ROLE')]
#[ORM\Index(columns: ['used'], name: 'IDX_USER_INVITES_USED')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_INVITES_TOKEN_SELECTOR', columns: ['token_selector'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class UserInvite
{
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 191)]
    private ?string $email = null;

    #[ORM\Column(name: 'token_selector', type: Types::STRING, length: 32)]
    private ?string $tokenSelector = null;

    #[ORM\Column(name: 'token_verifier_hash', type: Types::STRING, length: 255)]
    private ?string $tokenVerifierHash = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expiration = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $used = false;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Role::class)]
        #[ORM\JoinColumn(name: 'role_id', nullable: false, onDelete: 'CASCADE')]
        private Role $role,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addId();
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
