<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteEvent.
 */
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
     * @var
     */
    protected $type;

    /**
     * @param Loader $loader
     */
    public function __construct(Loader $loader, $type = 'main')
    {
        $this->loader     = $loader;
        $this->collection = new RouteCollection();
        $this->type       = $type;
    }

    /**
     * @param mixed $path
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
