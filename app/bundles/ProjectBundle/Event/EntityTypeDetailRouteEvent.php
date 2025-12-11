<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Event;

use Mautic\ProjectBundle\DTO\DetailRoute;
use Symfony\Contracts\EventDispatcher\Event;

final class EntityTypeDetailRouteEvent extends Event
{
    /**
     * @var array<string, ?DetailRoute>
     */
    private array $routes = [];

    public function addRoute(string $entityType, ?DetailRoute $route): void
    {
        $this->routes[$entityType] = $route;
    }

    public function getRoute(string $entityType): ?DetailRoute
    {
        if (array_key_exists($entityType, $this->routes)) {
            return $this->routes[$entityType];
        }

        // Default lives here (single place), not in the service or Twig.
        return new DetailRoute('mautic_'.$entityType.'_action', 'objectId', ['objectAction' => 'view']);
    }
}
