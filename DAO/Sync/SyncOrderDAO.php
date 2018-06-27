<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class SyncOrderDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class SyncOrderDAO
{
    /**
     * @var ObjectChangeDAO[]
     */
    private $objectsChanges = [];

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO)
    {
        $this->objectsChanges[$objectChangeDAO->getId()] = $objectChangeDAO;

        return $this;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getObjectsChanges()
    {
        return $this->objectsChanges;
    }
}
