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

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\Event;

class InternalObjectOwnerEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var int[]
     */
    private $objectIds;

    /**
     * Format: [object_id => owner_id].
     *
     * @var array
     */
    private $owners = [];

    /**
     * @param int[] $objectIds
     */
    public function __construct(ObjectInterface $object, array $objectIds)
    {
        $this->object    = $object;
        $this->objectIds = $objectIds;
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return int[]
     */
    public function getObjectIds(): array
    {
        return $this->objectIds;
    }

    public function getOwners(): array
    {
        return $this->owners;
    }

    public function setOwners(array $owners): void
    {
        $this->owners = $owners;
    }
}
