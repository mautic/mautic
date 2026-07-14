<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class IntegrationEntity extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string|null
     */
    private $integration;

    /**
     * @var string|null
     */
    private $integrationEntity;

    /**
     * @var string|null
     */
    private $integrationEntityId;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var \DateTimeInterface
     */
    private $lastSyncDate;

    /**
     * @var string|null
     */
    private $internalEntity;

    /**
     * @var int|null
     */
    private $internalEntityId;

    /**
     * @var array
     */
    private $internal;

    public function __construct()
    {
        $this->internal = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('integration_entity')
            ->setCustomRepositoryClass(IntegrationEntityRepository::class)
            ->addIndex(['integration', 'integration_entity', 'integration_entity_id'], 'integration_external_entity')
            ->addIndex(['integration', 'internal_entity', 'internal_entity_id'], 'integration_internal_entity')
            ->addIndex(['integration', 'internal_entity', 'integration_entity'], 'integration_entity_match')
            ->addIndex(['integration', 'last_sync_date'], 'integration_last_sync_date')
            ->addIndex(['internal_entity_id', 'integration_entity_id', 'internal_entity', 'integration_entity'], 'internal_integration_entity');

        $builder->addId();

        $builder->addDateAdded();

        $builder->addNullableField('integration', 'string');

        $builder->createField('integrationEntity', 'string')
            ->columnName('integration_entity')
            ->nullable()
            ->build();
        $builder->createField('integrationEntityId', 'string')
            ->columnName('integration_entity_id')
            ->nullable()
            ->build();
        $builder->createField('internalEntity', 'string')
            ->columnName('internal_entity')
            ->nullable()
            ->build();
        $builder->createField('internalEntityId', 'integer')
            ->columnName('internal_entity_id')
            ->nullable()
            ->build();

        $builder->createField('lastSyncDate', 'datetime')
            ->columnName('last_sync_date')
            ->nullable()
            ->build();

        $builder->addNullableField('internal', 'array');
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
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
    public function getIntegrationEntity()
    {
        return $this->integrationEntity;
    }

    /**
     * @param string $integrationEntity
     */
    public function setIntegrationEntity($integrationEntity): static
    {
        $this->integrationEntity = $integrationEntity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIntegrationEntityId()
    {
        return $this->integrationEntityId;
    }

    /**
     * @param string $integrationEntityId
     */
    public function setIntegrationEntityId($integrationEntityId): static
    {
        $this->integrationEntityId = $integrationEntityId;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastSyncDate()
    {
        return $this->lastSyncDate;
    }

    /**
     * @param \DateTime $lastSyncDate
     */
    public function setLastSyncDate($lastSyncDate): static
    {
        $this->lastSyncDate = $lastSyncDate;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalEntity()
    {
        return $this->internalEntity;
    }

    /**
     * @param string $internalEntity
     */
    public function setInternalEntity($internalEntity): static
    {
        $this->internalEntity = $internalEntity;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getInternalEntityId()
    {
        return $this->internalEntityId;
    }

    /**
     * @param int $internalEntityId
     */
    public function setInternalEntityId($internalEntityId): static
    {
        $this->internalEntityId = $internalEntityId;

        return $this;
    }

    /**
     * @return array
     */
    public function getInternal()
    {
        return $this->internal;
    }

    /**
     * @param array $internal
     */
    public function setInternal($internal): static
    {
        $this->internal = $internal;

        return $this;
    }
}
