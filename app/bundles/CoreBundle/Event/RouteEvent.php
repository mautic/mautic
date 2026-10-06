<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\EventDispatcher\Event;

final class RouteEvent extends Event
{
    private readonly RouteCollection $collection;

    public function __construct(
        private readonly Loader $loader,
        private readonly string $type = 'main',
    ) {
        $this->collection = new RouteCollection();
    }

    /**
     * @param string $path
     */
    public function addRoutes($path): void
    {
        $this->collection->addCollection($this->loader->import($path));
    }

    public function getCollection(): RouteCollection
    {
        return $this->collection;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
