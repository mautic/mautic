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
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\EntityMappingDAO;
use MauticPlugin\IntegrationsBundle\Exception\UnexpectedValueException;

/**
 * Class OrderDAO
 */
class OrderDAO
{
    /**
     * @var int
     */
    private $syncDateTime;

    /**
     * @var bool
     */
    private $isFirstTimeSync;

    /**
     * @var array|ObjectChangeDAO[]
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
     * @param ObjectChangeDAO $objectChange
     * @param                 $integrationObjectName
     * @param                 $integrationObjectId
     * @param null            $objectModifiedDate
     */
    public function addObjectMapping(ObjectChangeDAO $objectChange, $integrationObjectName, $integrationObjectId, $objectModifiedDate = null)
    {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration($objectChange->getIntegration())
            ->setInternalObjectName($objectChange->getMappedObject())
            ->setInternalObjectId($objectChange->getMappedObjectId())
            ->setIntegrationObjectName($integrationObjectName)
            ->setIntegrationObjectId($integrationObjectId)
            ->setLastSyncDate($objectModifiedDate);

        $this->objectMappings[] = $objectMapping;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return $this->objectMappings;
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
     * @return \DateTimeInterface
     */
    public function getSyncDateTime(): \DateTimeInterface
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
}
