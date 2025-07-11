<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Exception\UnexpectedValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;

class OrderDAO
{
    /**
     * @var ObjectChangeDAO[][]
     */
    private array $identifiedObjects = [];

    /**
     * @var ObjectChangeDAO[][]
     */
    private array $unidentifiedObjects = [];

    /**
     * Array of all changed objects.
     *
     * @var ObjectChangeDAO[][]
     */
    private array $changedObjects = [];

    /**
     * @var ObjectMapping[]
     */
    private array $objectMappings = [];

    /**
     * @var UpdatedObjectMappingDAO[]
     */
    private array $updatedObjectMappings = [];

    /**
     * @var RemappedObjectDAO[]
     */
    private array $remappedObjects = [];

    /**
     * @var ObjectChangeDAO[]
     */
    private array $deleteTheseObjects = [];

    private array $retryTheseLater = [];

    private int $objectCounter = 0;

    /**
     * @var NotificationDAO[]
     */
    private array $notifications = [];

    /**
     * @param bool   $isFirstTimeSync
     * @param string $integration
     */
    public function __construct(
        private \DateTimeInterface $syncDateTime,
        private $isFirstTimeSync,
        private $integration,
        private array $options = [],
    ) {
    }

    public function addObjectChange(ObjectChangeDAO $objectChangeDAO): self
    {
        if (!isset($this->identifiedObjects[$objectChangeDAO->getObject()])) {
            $this->identifiedObjects[$objectChangeDAO->getObject()]    = [];
            $this->unidentifiedObjects[$objectChangeDAO->getObject()]  = [];
            $this->changedObjects[$objectChangeDAO->getObject()]       = [];
        }

        $this->changedObjects[$objectChangeDAO->getObject()][] = $objectChangeDAO;
        ++$this->objectCounter;

        if ($objectChangeDAO->getObjectId()) {
            $this->identifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getObjectId()] = $objectChangeDAO;

            return $this;
        }

        // These objects are not already tracked and thus possibly need to be created
        $this->unidentifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getMappedObjectId()] = $objectChangeDAO;

        return $this;
    }

    /**
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
     * @return ObjectChangeDAO[][]
     */
    public function getIdentifiedObjects(): array
    {
        return $this->identifiedObjects;
    }

    /**
     * @return ObjectChangeDAO[][]
     */
    public function getUnidentifiedObjects(): array
    {
        return $this->unidentifiedObjects;
    }

    /**
     * Create a new mapping between the Mautic and Integration objects.
     *
     * @param string     $integrationObjectName
     * @param string|int $integrationObjectId
     */
    public function addObjectMapping(
        ObjectChangeDAO $objectChangeDAO,
        $integrationObjectName,
        $integrationObjectId,
        ?\DateTimeInterface $objectModifiedDate = null,
    ): void {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration($this->integration)
            ->setInternalObjectName($objectChangeDAO->getMappedObject())
            ->setInternalObjectId($objectChangeDAO->getMappedObjectId())
            ->setIntegrationObjectName($integrationObjectName)
            ->setIntegrationObjectId($integrationObjectId)
            ->setLastSyncDate($objectModifiedDate);

        $this->objectMappings[] = $objectMapping;
    }

    /**
     * Update an existing mapping in the case of conversions (i.e. Lead converted to Contact).
     *
     * @param mixed  $oldObjectId
     * @param string $oldObjectName
     * @param string $newObjectName
     * @param mixed  $newObjectId
     */
    public function remapObject($oldObjectName, $oldObjectId, $newObjectName, $newObjectId = null): void
    {
        if (null === $newObjectId) {
            $newObjectId = $oldObjectId;
        }

        $this->remappedObjects[$oldObjectId] = new RemappedObjectDAO($this->integration, $oldObjectName, $oldObjectId, $newObjectName, $newObjectId);
    }

    /**
     * Update the last sync date of an existing mapping.
     */
    public function updateLastSyncDate(ObjectChangeDAO $objectChangeDAO, ?\DateTimeInterface $objectModifiedDate = null): void
    {
        if (null === $objectModifiedDate) {
            $objectModifiedDate = new \DateTime();
        }

        $this->updatedObjectMappings[] = new UpdatedObjectMappingDAO(
            $this->integration,
            $objectChangeDAO->getObject(),
            $objectChangeDAO->getObjectId(),
            $objectModifiedDate
        );
    }

    /**
     * Mark an object as deleted in the integration so Mautic doesn't continue to attempt to sync it.
     */
    public function deleteObject(ObjectChangeDAO $objectChangeDAO): void
    {
        $this->deleteTheseObjects[] = $objectChangeDAO;
    }

    /**
     * If there is a temporary issue with syncing the object, tell the sync engine to not wipe out the tracked changes on Mautic's object fields
     * so that they are attempted again for the next sync.
     */
    public function retrySyncLater(ObjectChangeDAO $objectChangeDAO): void
    {
        if (!isset($this->retryTheseLater[$objectChangeDAO->getMappedObject()])) {
            $this->retryTheseLater[$objectChangeDAO->getMappedObject()] = [];
        }

        $this->retryTheseLater[$objectChangeDAO->getMappedObject()][$objectChangeDAO->getMappedObjectId()] = $objectChangeDAO;
    }

    public function noteObjectSyncIssue(ObjectChangeDAO $objectChangeDAO, string $message): void
    {
        $this->notifications[] = new NotificationDAO($objectChangeDAO, $message);
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return $this->objectMappings;
    }

    /**
     * @return UpdatedObjectMappingDAO[]
     */
    public function getUpdatedObjectMappings(): array
    {
        return $this->updatedObjectMappings;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getDeletedObjects(): array
    {
        return $this->deleteTheseObjects;
    }

    /**
     * @return RemappedObjectDAO[]
     */
    public function getRemappedObjects(): array
    {
        return $this->remappedObjects;
    }

    /**
     * @return NotificationDAO[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getSuccessfullySyncedObjects(): array
    {
        $synced = [];
        foreach ($this->changedObjects as $objectChanges) {
            foreach ($objectChanges as $objectChange) {
                if (isset($this->retryTheseLater[$objectChange->getMappedObject()][$objectChange->getMappedObjectId()])) {
                    continue;
                }

                $synced[] = $objectChange;
            }
        }

        return $synced;
    }

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
    public function getSyncDateTime(): \DateTimeInterface
    {
        return $this->syncDateTime;
    }

    public function isFirstTimeSync(): bool
    {
        return $this->isFirstTimeSync;
    }

    public function shouldSync(): bool
    {
        return !empty($this->changedObjects);
    }

    public function getObjectCount(): int
    {
        return $this->objectCounter;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
