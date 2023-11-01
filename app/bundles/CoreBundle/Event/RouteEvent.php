<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

class RouteEvent extends Event
{
    /**
     * @var Loader
     */
    protected $loader;

    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @var string
     */
    protected $type;

    public function __construct(Loader $loader, $type = 'main')
    {
        $this->loader     = $loader;
        $this->collection = new RouteCollection();
        $this->type       = $type;
    }

    /**
     * @param string $path
     */
    public function addRoutes($path)
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
