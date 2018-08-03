<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order;

/**
 * Class OrderDAO
 */
class OrderDAO
{
    /**
     * @var int
     */
    private $syncTimestamp;

    /**
     * @var array|ObjectChangeDAO[]
     */
    private $identifiedObjects = [];

    /**
     * @var array
     */
    private $unidentifiedObjects = [];

    /**
     * OrderDAO constructor.
     *
     * @param int $syncTimestamp
     */
    public function __construct($syncTimestamp)
    {
        $this->syncTimestamp = (int) $syncTimestamp;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     *
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO): OrderDAO
    {
        if (!isset($this->identifiedObjects[$objectChangeDAO->getObject()])) {
            $this->identifiedObjects[$objectChangeDAO->getObject()]      = [];
            $this->unidentifiedObjects[$objectChangeDAO->getObject()] = [];
        }

        if ($knownId = $objectChangeDAO->getObjectId()) {
            $this->identifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getObjectId()] = $objectChangeDAO;

            return $this;
        }

        // These objects are not already tracked and thus possibly need to be created
        $this->unidentifiedObjects[$objectChangeDAO->getObject()][$objectChangeDAO->getMappedId()] = $objectChangeDAO;

        return $this;
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
     * @param string $object
     *
     * @return array
     */
    public function getObjectKnownIds(string $object): array
    {
        if (!array_key_exists($object, $this->identifiedObjects)) {
            return [];
        }

        return array_keys($this->identifiedObjects[$object]);
    }

    /**
     * @return int
     */
    public function getSyncTimestamp(): int
    {
        return $this->syncTimestamp;
    }
}
