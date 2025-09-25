<?php

namespace MauticPlugin\MauticUliBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class UniqueLogin
{
    public const TABLE_NAME = 'plugin_uli_unique_logins';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var \DateTime
     */
    private $ttl;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(UniqueLoginRepository::class)
            ->addIndex(['hash'], 'uli_hash_search')
            ->addIndex(['ttl'], 'uli_ttl_search')
            ->addIndex(['user_id'], 'uli_user_search');

        $builder->addId();

        $builder->createField('hash', 'string')
            ->columnName('hash')
            ->length(64)
            ->unique()
            ->build();

        $builder->createField('userId', 'integer')
            ->columnName('user_id')
            ->build();

        $builder->createField('ttl', 'datetime')
            ->columnName('ttl')
            ->build();

        $builder->createField('dateCreated', 'datetime')
            ->columnName('date_created')
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTtl(): ?\DateTime
    {
        return $this->ttl;
    }

    public function setTtl(\DateTime $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->ttl >= new \DateTime();
    }
}