<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\EventDispatcher\Event;

class RouteEvent extends Event
{
    protected \Symfony\Component\Routing\RouteCollection $collection;

    /**
     * @param string $type
     */
    public function __construct(
        protected Loader $loader,
        protected $type = 'main'
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

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
