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

class InternalObjectRouteEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string|null
     */
    private $route;

    public function __construct(ObjectInterface $object, int $id)
    {
        $this->object = $object;
        $this->id     = $id;
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): void
    {
        $this->route = $route;
    }
}
