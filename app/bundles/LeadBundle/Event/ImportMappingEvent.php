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
