<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;

use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Exception\UnexpectedValueException;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

/**
 * Class OrderDAO
 */
class OrderDAO
{
    /**
     * @var \DateTime
     */
    private $syncDateTime;

    /**
     * @var bool
     */
    private $isFirstTimeSync;

    /**
     * @var array
     */
    private $identifiedObjects = [];

    /**
     * @var array
     */
    private $unidentifiedObjects = [];

    /**
     * Array of all changed objects.
     *
     * @var array|ObjectChangeDAO[]
     */
    private $changedObjects = [];

    /**
     * @var array|ObjectMapping
     */
    private $objectMappings = [];

    /**
     * @var array
     */
    private $updatedObjectMappings = [];

    /**
     * @var int
     */
    private $objectCounter = 0;

    /**
     * OrderDAO constructor.
     *
     * @param \DateTimeInterface $syncDateTime
     * @param bool               $isFirstTimeSync
     */
    public function __construct(\DateTimeInterface $syncDateTime, $isFirstTimeSync)
    {
        $this->syncDateTime    = $syncDateTime;
        $this->isFirstTimeSync = $isFirstTimeSync;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     *
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO): OrderDAO
    {
        if (!isset($this->identifiedObjects[$objectChangeDAO->getObject()])) {
            $this->identifiedObjects[$objectChangeDAO->getObject()]   = [];
            $this->unidentifiedObjects[$objectChangeDAO->getObject()] = [];
            $this->changedObjects[$objectChangeDAO->getObject()]      = [];
        }

        $this->changedObjects[$objectChangeDAO->getObject()][] = $objectChangeDAO;
        $this->objectCounter++;

        if ($knownId = $objectChangeDAO->getObjectId()) {
            $this->identifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getObjectId()] = $objectChangeDAO;

            return $this;
        }

        // These objects are not already tracked and thus possibly need to be created
        $this->unidentifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getMappedObjectId()] = $objectChangeDAO;

        return $this;
    }

    /**
     * @param string $objectType
     *
     * @return array
     *
     * @throws UnexpectedValueException
     */
    public function getChangedObjectsByObjectType(string $objectType): array
    {
        if (isset($this->changedObjects[$objectType])) {
            return $this->changedObjects[$objectType];
        }

        throw new UnexpectedValueException("There are no change objects for object type '$objectType'");
    }

    /**
     * @return array
     */
    public function getIdentifiedObjects(): array
    {
        return $this->identifiedObjects;
    }

    /**
     * @return array
     */
    public function getUnidentifiedObjects(): array
    {
        return $this->unidentifiedObjects;
    }

    /**
     * @param                $integration
     * @param                $internalObjectName
     * @param                $internalObjectId
     * @param                $integrationObjectName
     * @param                $integrationObjectId
     * @param \DateTime|null $objectModifiedDate
     */
    public function addObjectMapping(
        $integration,
        $internalObjectName,
        $internalObjectId,
        $integrationObjectName,
        $integrationObjectId,
        \DateTime $objectModifiedDate = null
    ) {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration($integration)
            ->setInternalObjectName($internalObjectName)
            ->setInternalObjectId($internalObjectId)
            ->setIntegrationObjectName($integrationObjectName)
            ->setIntegrationObjectId($integrationObjectId)
            ->setLastSyncDate($objectModifiedDate);

        $this->objectMappings[] = $objectMapping;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @param mixed           $newIntegrationObjectId
     * @param null|string     $newIntegrationObjectName
     * @param \DateTime|null  $objectModifiedDate
     */
    public function updateObjectMapping(ObjectChangeDAO $objectChangeDAO, $newIntegrationObjectId, $newIntegrationObjectName = null, \DateTime $objectModifiedDate = null)
    {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $this->updatedObjectMappings[] = new UpdatedObjectMappingDAO($objectChangeDAO, $newIntegrationObjectId, $newIntegrationObjectName, $objectModifiedDate);
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @param \DateTime|null  $objectModifiedDate
     */
    public function updateLastSyncDate(ObjectChangeDAO $objectChangeDAO, \DateTime $objectModifiedDate = null)
    {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $this->updatedObjectMappings[] = new UpdatedObjectMappingDAO($objectChangeDAO, $objectChangeDAO->getObjectId(), $objectChangeDAO->getObject(), $objectModifiedDate);
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return $this->objectMappings;
    }

    /**
     * @return array
     */
    public function getUpdatedObjectMappings(): array
    {
        return $this->updatedObjectMappings;
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getIdentifiedObjectIds(string $object): array
    {
        if (!array_key_exists($object, $this->identifiedObjects)) {
            return [];
        }

        return array_keys($this->identifiedObjects[$object]);
    }

    /**
     * @return \DateTime
     */
    public function getSyncDateTime(): \DateTime
    {
        return $this->syncDateTime;
    }

    /**
     * @return bool
     */
    public function isFirstTimeSync(): bool
    {
        return $this->isFirstTimeSync;
    }

    /**
     * @return bool
     */
    public function shouldSync(): bool
    {
        return !empty($this->changedObjects);
    }

    /**
     * @return int
     */
    public function getObjectCount(): int
    {
        return $this->objectCounter;
    }
}
