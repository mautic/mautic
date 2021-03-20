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

final class ImportInitEvent extends Event
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
     * @var string|null
     */
    private $objectSingular;

    /**
     * Object name for humans. Will go through translator.
     *
     * @var string|null
     */
    private $objectName;

    /**
     * @var string|null
     */
    private $activeLink;

    /**
     * @var string
     */
    private $indexRoute;

    /**
     * @var array
     */
    private $indexRouteParams = [];

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

    public function setObjectSingular(?string $objectSingular): void
    {
        $this->objectSingular = $objectSingular;
    }

    public function getObjectSingular(): ?string
    {
        return $this->objectSingular;
    }

    public function setObjectName(?string $objectName): void
    {
        $this->objectName = $objectName;
    }

    public function getObjectName(): ?string
    {
        return $this->objectName;
    }

    public function setActiveLink(?string $activeLink): void
    {
        $this->activeLink = $activeLink;
    }

    public function getActiveLink(): ?string
    {
        return $this->activeLink;
    }

    public function setIndexRoute(?string $indexRoute, array $routeParams = [])
    {
        $this->indexRoute       = $indexRoute;
        $this->indexRouteParams = $routeParams;
    }

    public function getIndexRoute(): ?string
    {
        return $this->indexRoute;
    }

    public function getIndexRouteParams(): array
    {
        return $this->indexRouteParams;
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
