<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;

class OrderResultsDAO
{
    /**
     * @var ObjectMapping[]
     */
    private $newObjectMappings;

    /**
     * @var ObjectMapping[]
     */
    private $updatedObjectMappings;

    /**
     * @var RemappedObjectDAO[]
     */
    private $remappedObjects;

    /**
     * @var ObjectChangeDAO[]
     */
    private $deletedObjects;

    public function __construct(array $newObjectMappings, array $updatedObjectMappings, array $remappedObjects, array $deletedObjects)
    {
        $this->newObjectMappings     = $newObjectMappings;
        $this->updatedObjectMappings = $updatedObjectMappings;
        $this->remappedObjects       = $remappedObjects;
        $this->deletedObjects        = $deletedObjects;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return array_merge($this->newObjectMappings, $this->updatedObjectMappings);
    }

    /**
     * @return ObjectMapping[]
     */
    public function getNewObjectMappings(): array
    {
        return $this->newObjectMappings;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getUpdatedObjectMappings(): array
    {
        return $this->updatedObjectMappings;
    }

    /**
     * @return RemappedObjectDAO[]
     */
    public function getRemappedObjects(): array
    {
        return $this->remappedObjects;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getDeletedObjects(): array
    {
        return $this->deletedObjects;
    }
}
