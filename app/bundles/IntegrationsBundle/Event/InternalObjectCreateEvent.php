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

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\Event;

class InternalObjectCreateEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var array
     */
    private $createObjects;

    /**
     * @var ObjectMapping[]
     */
    private $objectMappings = [];

    public function __construct(ObjectInterface $object, array $createObjects)
    {
        $this->object        = $object;
        $this->createObjects = $createObjects;
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getCreateObjects(): array
    {
        return $this->createObjects;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return $this->objectMappings;
    }

    /**
     * @param ObjectMapping[] $objectMappings
     */
    public function setObjectMappings(array $objectMappings): void
    {
        $this->objectMappings = $objectMappings;
    }
}
