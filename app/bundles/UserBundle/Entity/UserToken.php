<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class UserToken
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $authorizator;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var \DateTimeInterface|null
     */
    private $expiration;

    /**
     * @var bool
     */
    private $oneTimeOnly = true;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('user_tokens')
            ->setCustomRepositoryClass(UserTokenRepository::class);

        $builder->addId();

        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('authorizator', 'string')
            ->length(32)
            ->build();

        $builder->createField('secret', 'string')
            ->length(120)
            ->unique()
            ->build();

        $builder->createField('expiration', 'datetime')
            ->nullable()
            ->build();

        $builder->createField('oneTimeOnly', 'boolean')
            ->columnName('one_time_only')
            ->build();
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\Mautic\UserBundle\Entity\User $user): static
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

    public function setAuthorizator(string $authorizator): static
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

    public function setExpiration(?\DateTime $expiration = null): static
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

    public function setOneTimeOnly(bool $oneTimeOnly = true): static
    {
        $this->oneTimeOnly = $oneTimeOnly;

        return $this;
    }
}
