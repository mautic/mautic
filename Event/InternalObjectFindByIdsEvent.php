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

use MauticPlugin\IntegrationsBundle\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\Event;

class InternalObjectFindByIdsEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var int[]
     */
    private $ids;

    /**
     * @var array
     */
    private $objects = [];

    /**
     * @param ObjectInterface $object
     * @param int[]           $ids
     */
    public function __construct(ObjectInterface $object, array $ids)
    {
        $this->object = $object;
        $this->ids    = $ids;
    }

    /**
     * @return ObjectInterface
     */
    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @return array
     */
    public function getFoundObjects(): array
    {
        return $this->objects;
    }

    /**
     * @param array $objects
     */
    public function setFoundObjects(array $objects): void
    {
        $this->objects = $objects;
    }
}
