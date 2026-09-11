<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: UserTokenRepository::class)]
#[ORM\Table(name: 'user_tokens')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class UserToken
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 32)]
    private $authorizator;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private $secret;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $expiration;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'one_time_only', type: 'boolean')]
    private $oneTimeOnly = true;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthorizator()
    {
        return $this->authorizator;
    }

    /**
     * @param string $authorizator
     */
    public function setAuthorizator($authorizator): static
    {
        $this->authorizator = $authorizator;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Use \Mautic\UserBundle\Entity\UserTokenRepositoryInterface::generateSecret to get valid secret.
     *
     * @param string $secret
     */
    public function setSecret($secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * @param \DateTime|null $expiration
     */
    public function setExpiration($expiration = null): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOneTimeOnly()
    {
        return $this->oneTimeOnly;
    }

    /**
     * @param bool $oneTimeOnly
     */
    public function setOneTimeOnly($oneTimeOnly = true): static
    {
        $this->oneTimeOnly = $oneTimeOnly;

        return $this;
    }
}
