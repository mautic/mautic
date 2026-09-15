<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

#[ORM\Entity(repositoryClass: IntegrationEntityRepository::class)]
#[ORM\Table(name: 'integration_entity')]
#[ORM\Index(columns: ['integration', 'integration_entity', 'integration_entity_id'], name: 'integration_external_entity')]
#[ORM\Index(columns: ['integration', 'internal_entity', 'internal_entity_id'], name: 'integration_internal_entity')]
#[ORM\Index(columns: ['integration', 'internal_entity', 'integration_entity'], name: 'integration_entity_match')]
#[ORM\Index(columns: ['integration', 'last_sync_date'], name: 'integration_last_sync_date')]
#[ORM\Index(columns: ['internal_entity_id', 'integration_entity_id', 'internal_entity', 'integration_entity'], name: 'internal_integration_entity')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
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
    #[ORM\Column(name: 'integration_entity', type: 'string', length: 191, nullable: true)]
    private $integrationEntity;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'integration_entity_id', type: 'string', length: 191, nullable: true)]
    private $integrationEntityId;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'last_sync_date', type: 'datetime', nullable: true)]
    private $lastSyncDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'internal_entity', type: 'string', length: 191, nullable: true)]
    private $internalEntity;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'internal_entity_id', type: 'integer', nullable: true)]
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

        $builder->addId();

        $builder->addDateAdded();

        $builder->addNullableField('integration', 'string');

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
