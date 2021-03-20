<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class ImportMappingEvent extends Event
{
    /**
     * @var string
     */
    private $routeObjectName;

    /**
     * @var bool
     */
    private $objectSupported = false;

    /**
     * @var array
     */
    private $fields = [];

    public function __construct(string $routeObjectName)
    {
        $this->routeObjectName = $routeObjectName;
    }

    public function getRouteObjectName(): string
    {
        return $this->routeObjectName;
    }

    public function objectIsSupported(): bool
    {
        return $this->objectSupported;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Check if the import is for said route object and notes if the object exist.
     */
    public function importIsForRouteObject(string $routeObject): bool
    {
        if ($this->getRouteObjectName() === $routeObject) {
            $this->objectSupported = true;

            return true;
        }

        return false;
    }

    public function setObjectIsSupported(bool $objectSupported): void
    {
        $this->objectSupported = $objectSupported;
    }
}
