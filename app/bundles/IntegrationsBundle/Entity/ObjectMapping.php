<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: ObjectMappingRepository::class)]
#[ORM\Table(name: 'sync_object_mapping')]
#[ORM\Index(columns: ['internal_object_id'], name: 'internal_object_id_idx')]
#[ORM\Index(columns: ['integration', 'integration_object_name', 'integration_object_id', 'integration_reference_id'], name: 'integration_object')]
#[ORM\Index(columns: ['integration', 'integration_object_name', 'integration_reference_id', 'integration_object_id'], name: 'integration_reference')]
#[ORM\Index(columns: ['integration', 'internal_object_name', 'last_sync_date'], name: 'integration_integration_object_name_last_sync_date')]
#[ORM\Index(columns: ['integration', 'last_sync_date'], name: 'integration_last_sync_date')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ObjectMapping
{
    /**
     * @var int
     */
    private $id;

    #[ORM\Column(name: 'date_created', type: Types::DATETIME_MUTABLE)]
    private readonly ?\DateTimeInterface $dateCreated;

    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $integration;

    /**
     * @var string
     */
    #[ORM\Column(name: 'internal_object_name', type: Types::STRING, length: 191)]
    private $internalObjectName;

    /**
     * @var string
     */
    private $internalObjectId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'integration_object_name', type: Types::STRING, length: 191)]
    private $integrationObjectName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'integration_object_id', type: Types::STRING, length: 191)]
    private $integrationObjectId;

    #[ORM\Column(name: 'last_sync_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastSyncDate;

    /**
     * @var array
     */
    #[ORM\Column(name: 'internal_storage', type: Types::JSON)]
    private $internalStorage = [];

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_deleted', type: Types::BOOLEAN)]
    private $isDeleted = false;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'integration_reference_id', type: Types::STRING, length: 191, nullable: true)]
    private $integrationReferenceId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addBigIntIdField('internalObjectId', 'internal_object_id', false);
    }

    public function __construct(?\DateTime $dateCreated = null)
    {
        $dateCreated ??= new \DateTime();

        $this->dateCreated  = $dateCreated;
        $this->lastSyncDate = $dateCreated;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * @return string|null
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @param string $integration
     */
    public function setIntegration($integration): static
    {
        $this->integration = $integration;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalObjectName()
    {
        return $this->internalObjectName;
    }

    /**
     * @param string $internalObjectName
     */
    public function setInternalObjectName($internalObjectName): static
    {
        $this->internalObjectName = $internalObjectName;

        return $this;
    }

    public function getInternalObjectId(): int
    {
        return (int) $this->internalObjectId;
    }

    /**
     * @param int $internalObjectId
     */
    public function setInternalObjectId($internalObjectId): static
    {
        $this->internalObjectId = (string) $internalObjectId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntegrationObjectName()
    {
        return $this->integrationObjectName;
    }

    /**
     * @param string $integrationObjectName
     */
    public function setIntegrationObjectName($integrationObjectName): static
    {
        $this->integrationObjectName = $integrationObjectName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntegrationObjectId()
    {
        return $this->integrationObjectId;
    }

    /**
     * @param string $integrationObjectId
     */
    public function setIntegrationObjectId($integrationObjectId): static
    {
        $this->integrationObjectId = $integrationObjectId;

        return $this;
    }

    public function getLastSyncDate(): ?\DateTimeInterface
    {
        return $this->lastSyncDate;
    }

    public function setLastSyncDate(?\DateTimeInterface $lastSyncDate): static
    {
        $lastSyncDate ??= new \DateTime();

        $this->lastSyncDate = $lastSyncDate;

        return $this;
    }

    /**
     * @return array
     */
    public function getInternalStorage()
    {
        return $this->internalStorage;
    }

    /**
     * @param array $internalStorage
     */
    public function setInternalStorage($internalStorage): static
    {
        $this->internalStorage = $internalStorage;

        return $this;
    }

    public function appendToInternalStorage($key, $value): static
    {
        $this->internalStorage[$key] = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     */
    public function setIsDeleted($isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntegrationReferenceId()
    {
        return $this->integrationReferenceId;
    }

    /**
     * @param string|null $integrationReferenceId
     */
    public function setIntegrationReferenceId($integrationReferenceId): static
    {
        $this->integrationReferenceId = $integrationReferenceId;

        return $this;
    }
}
