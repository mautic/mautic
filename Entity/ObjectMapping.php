<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Entity;

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
     * @var \DateTimeInterface|null
     */
    private $lastSyncDate;

    /**
     * @var array
     */
    private $internalStorage = [];

    /**
     * @param ORM\ClassMetadata $metadata
     *
     * @return void
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('sync_object_mapping')
            ->setCustomRepositoryClass(ObjectMappingRepository::class)
            ->addIndex(['integration', 'integration_object', 'integration_object_id'], 'integration_object')
            ->addIndex(['integration', 'internal_object', 'internal_object_id'], 'internal_object')
            ->addIndex(['integration', 'internal_object', 'integration_object'], 'object_match')
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

        $builder
            ->createField('internalObjectId', Type::INTEGER)
            ->columnName('internal_object_id')
            ->build();

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
    }

    /**
     * ObjectMapping constructor.
     *
     * @param \DateTime|null $dateCreated
     */
    public function __construct(\DateTime $dateCreated = null)
    {
        if (null === $dateCreated) {
            $dateCreated = new \DateTime();
        }

        $this->dateCreated = $dateCreated;
    }

    /**
     * @return int
     */
    public function getId(): int
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
    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
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
    public function getInternalObjectName(): string
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
    public function getInternalObjectId(): int
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
    public function getIntegrationObjectName(): string
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
    public function getIntegrationObjectId(): string
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
     * @return \DateTimeInterface|null
     */
    public function getLastSyncDate(): ?\DateTimeInterface
    {
        return $this->lastSyncDate;
    }

    /**
     * @param \DateTimeInterface|null $lastSyncDate
     *
     * @return ObjectMapping
     */
    public function setLastSyncDate(\DateTimeInterface $lastSyncDate)
    {
        $this->lastSyncDate = $lastSyncDate;

        return $this;
    }

    /**
     * @return array
     */
    public function getInternalStorage(): array
    {
        return $this->internalStorage;
    }

    /**
     * @param array $internalStorage
     *
     * @return ObjectMapping
     */
    public function setInternalStorage(array $internalStorage)
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

}