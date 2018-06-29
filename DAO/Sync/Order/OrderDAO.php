<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order;

/**
 * Class SyncOrderDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order
 */
class OrderDAO
{
    /**
     * @var int
     */
    private $syncTimestamp;

    /**
     * @var ObjectChangeDAO[]
     */
    private $objectsChanges = [];

    /**
     * OrderDAO constructor.
     * @param int $syncTimestamp
     */
    public function __construct($syncTimestamp)
    {
        $this->syncTimestamp = $syncTimestamp;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO)
    {
        $this->objectsChanges[$objectChangeDAO->getObjectId()] = $objectChangeDAO;

        return $this;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getObjectsChanges()
    {
        return $this->objectsChanges;
    }

    /**
     * @return int
     */
    public function getSyncTimestamp()
    {
        return $this->syncTimestamp;
    }
}
