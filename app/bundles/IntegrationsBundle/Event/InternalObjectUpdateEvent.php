<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Event;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\Event;

class InternalObjectUpdateEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var array
     */
    private $identifiedObjectIds;

    /**
     * @var array
     */
    private $updateObjects;

    /**
     * @var UpdatedObjectMappingDAO[]
     */
    private $updatedObjectMappings = [];

    /**
     * @param ObjectInterface $object
     * @param array           $identifiedObjectIds
     * @param array           $updateObjects
     */
    public function __construct(ObjectInterface $object, array $identifiedObjectIds, array $updateObjects)
    {
        $this->object              = $object;
        $this->identifiedObjectIds = $identifiedObjectIds;
        $this->updateObjects       = $updateObjects;
    }

    /**
     * @return ObjectInterface
     */
    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return array
     */
    public function getIdentifiedObjectIds(): array
    {
        return $this->identifiedObjectIds;
    }

    /**
     * @return array
     */
    public function getUpdateObjects(): array
    {
        return $this->updateObjects;
    }

    /**
     * @return UpdatedObjectMappingDAO[]
     */
    public function getUpdatedObjectMappings(): array
    {
        return $this->updatedObjectMappings;
    }

    /**
     * @param UpdatedObjectMappingDAO[] $updatedObjectMappings
     */
    public function setUpdatedObjectMappings(array $updatedObjectMappings): void
    {
        $this->updatedObjectMappings = $updatedObjectMappings;
    }
}
