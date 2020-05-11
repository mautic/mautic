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

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
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

    public function __construct(ObjectInterface $object, array $identifiedObjectIds, array $updateObjects)
    {
        $this->object              = $object;
        $this->identifiedObjectIds = $identifiedObjectIds;
        $this->updateObjects       = $updateObjects;
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getIdentifiedObjectIds(): array
    {
        return $this->identifiedObjectIds;
    }

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
