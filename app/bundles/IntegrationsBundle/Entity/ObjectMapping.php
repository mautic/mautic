<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class ObjectMapping
{
    /**
     * @var int
     */
    private $id;

    private readonly ?\DateTimeInterface $dateCreated;

    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $internalObjectName;

    /**
     * @var string
     */
    private $internalObjectId;

    /**
     * @var string
     */
    private $integrationObjectName;

    /**
     * @var string
     */
    private $integrationObjectId;

    private ?\DateTimeInterface $lastSyncDate;

    /**
     * @var array
     */
    private $internalStorage = [];

    /**
     * @var bool
     */
    private $isDeleted = false;

    /**
     * @var string|null
     */
    private $integrationReferenceId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('sync_object_mapping')
            ->setCustomRepositoryClass(ObjectMappingRepository::class)
            ->addIndex(['internal_object_id'], 'internal_object_id_idx')
            ->addIndex(['integration', 'integration_object_name', 'integration_object_id', 'integration_reference_id'], 'integration_object')
            ->addIndex(['integration', 'integration_object_name', 'integration_reference_id', 'integration_object_id'], 'integration_reference')
            ->addIndex(['integration', 'internal_object_name', 'last_sync_date'], 'integration_integration_object_name_last_sync_date')
            ->addIndex(['integration', 'last_sync_date'], 'integration_last_sync_date');

        $builder->addId();

        $builder
            ->createField('dateCreated', Types::DATETIME_MUTABLE)
            ->columnName('date_created')
            ->build();

        $builder
            ->createField('integration', Types::STRING)
            ->build();

        $builder
            ->createField('internalObjectName', Types::STRING)
            ->columnName('internal_object_name')
            ->build();

        $builder->addBigIntIdField('internalObjectId', 'internal_object_id', false);

        $builder
            ->createField('integrationObjectName', Types::STRING)
            ->columnName('integration_object_name')
            ->build();

        // Must be a string as not all IDs are integer based
        $builder
            ->createField('integrationObjectId', Types::STRING)
            ->columnName('integration_object_id')
            ->build();

        $builder
            ->createField('lastSyncDate', Types::DATETIME_MUTABLE)
            ->columnName('last_sync_date')
            ->build();

        $builder
            ->createField('internalStorage', Types::JSON)
            ->columnName('internal_storage')
            ->build();

        $builder
            ->createField('isDeleted', Types::BOOLEAN)
            ->columnName('is_deleted')
            ->build();

        $builder
            ->createField('integrationReferenceId', Types::STRING)
            ->columnName('integration_reference_id')
            ->nullable()
            ->build();
    }

    public function __construct(?\DateTime $dateCreated = null)
    {
        if (null === $dateCreated) {
            $dateCreated = new \DateTime();
        }

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

    /**
     * @param \DateTimeInterface|null $lastSyncDate
     */
    public function setLastSyncDate($lastSyncDate): static
    {
        if (null === $lastSyncDate) {
            $lastSyncDate = new \DateTime();
        }

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
