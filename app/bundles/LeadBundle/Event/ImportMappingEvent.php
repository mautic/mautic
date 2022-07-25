<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class ImportMappingEvent extends CommonEvent
{
    public string $routeObjectName;
    public bool $objectSupported = false;
    public array $fields         = [];

    public function __construct(string $routeObjectName)
    {
        $this->routeObjectName = $routeObjectName;
    }

    /**
     * Check if the import is for said route object and notes if the object exist.
     */
    public function importIsForRouteObject(string $routeObject): bool
    {
        if ($this->routeObjectName === $routeObject) {
            $this->objectSupported = true;

            return true;
        }

        return false;
    }
}
