<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class ImportInitEvent extends CommonEvent
{
    public bool $objectSupported   = false;

    public ?string $objectSingular = null;

    public ?string $objectName     = null; // Object name for humans. Will go through translator.

    public ?string $activeLink     = null;

    public ?string $indexRoute     = null;

    public array $indexRouteParams = [];

    public function __construct(
        public string $routeObjectName,
    ) {
    }

    public function setIndexRoute(?string $indexRoute, array $routeParams = []): void
    {
        $this->indexRoute       = $indexRoute;
        $this->indexRouteParams = $routeParams;
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
