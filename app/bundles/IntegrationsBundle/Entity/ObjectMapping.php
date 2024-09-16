<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class ObjectMapping
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime|null
     */
    private $dateCreated;

    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $internalObjectName;

    /**
     * @var int
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

    /**
     * @var \DateTimeInterface
     */
    private $lastSyncDate;

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
            ->addIndex(['integration', 'integration_object_name', 'integration_object_id', 'integration_reference_id'], 'integration_object')
            ->addIndex(['integration', 'integration_object_name', 'integration_reference_id', 'integration_object_id'], 'integration_reference')
            ->addIndex(['integration', 'last_sync_date'], 'integration_last_sync_date');

        $builder->addId();

        $builder
            ->createField('dateCreated', Type::DATETIME)
            ->columnName('date_created')
            ->build();

        $builder
            ->createField('integration', Type::STRING)
            ->build();

        $builder
            ->createField('internalObjectName', Type::STRING)
            ->columnName('internal_object_name')
            ->build();

        $builder->addBigIntIdField('internalObjectId', 'internal_object_id', false);

        $builder
            ->createField('integrationObjectName', Type::STRING)
            ->columnName('integration_object_name')
            ->build();

        // Must be a string as not all IDs are integer based
        $builder
            ->createField('integrationObjectId', Type::STRING)
            ->columnName('integration_object_id')
            ->build();

        $builder
            ->createField('lastSyncDate', Type::DATETIME)
            ->columnName('last_sync_date')
            ->build();

        $builder
            ->createField('internalStorage', Type::JSON_ARRAY)
            ->columnName('internal_storage')
            ->build();

        $builder
            ->createField('isDeleted', Type::BOOLEAN)
            ->columnName('is_deleted')
            ->build();

        $builder
            ->createField('integrationReferenceId', Type::STRING)
            ->columnName('integration_reference_id')
            ->nullable()
            ->build();
    }

    /**
     * ObjectMapping constructor.
     *
     * @throws \Exception
     */
    public function __construct(?\DateTime $dateCreated = null)
    {
        if (null === $dateCreated) {
            $dateCreated = new \DateTime();
        }

        $this->dateCreated  = $dateCreated;
        $this->lastSyncDate = $dateCreated;
    }

    /**
     * @return int|null ?int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return ObjectMapping
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @param string $integration
     *
     * @return ObjectMapping
     */
    public function setIntegration($integration)
    {
        $this->integration = $integration;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalObjectName()
    {
        return $this->internalObjectName;
    }

    /**
     * @param string $internalObjectName
     *
     * @return ObjectMapping
     */
    public function setInternalObjectName($internalObjectName)
    {
        $this->internalObjectName = $internalObjectName;

        return $this;
    }

    /**
     * @return int
     */
    public function getInternalObjectId()
    {
        return $this->internalObjectId;
    }

    /**
     * @param int $internalObjectId
     *
     * @return ObjectMapping
     */
    public function setInternalObjectId($internalObjectId)
    {
        $this->internalObjectId = $internalObjectId;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntegrationObjectName()
    {
        return $this->integrationObjectName;
    }

    /**
     * @param string $integrationObjectName
     *
     * @return ObjectMapping
     */
    public function setIntegrationObjectName($integrationObjectName)
    {
        $this->integrationObjectName = $integrationObjectName;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntegrationObjectId()
    {
        return $this->integrationObjectId;
    }

    /**
     * @param string $integrationObjectId
     *
     * @return ObjectMapping
     */
    public function setIntegrationObjectId($integrationObjectId)
    {
        $this->integrationObjectId = $integrationObjectId;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastSyncDate()
    {
        return $this->lastSyncDate;
    }

    /**
     * @param \DateTimeInterface|null $lastSyncDate
     *
     * @return ObjectMapping
     *
     * @throws \Exception
     */
    public function setLastSyncDate($lastSyncDate)
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
     *
     * @return ObjectMapping
     */
    public function setInternalStorage($internalStorage)
    {
        $this->internalStorage = $internalStorage;

        return $this;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function appendToInternalStorage($key, $value)
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
     *
     * @return ObjectMapping
     */
    public function setIsDeleted($isDeleted)
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
     *
     * @return ObjectMapping
     */
    public function setIntegrationReferenceId($integrationReferenceId)
    {
        $this->integrationReferenceId = $integrationReferenceId;

        return $this;
    }
}
