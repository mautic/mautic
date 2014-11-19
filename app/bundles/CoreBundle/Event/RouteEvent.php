<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteEvent
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
     * @param Loader $loader
     */
    public function __construct(Loader &$loader)
    {
        $this->loader     =& $loader;
        $this->collection = new RouteCollection();
    }

    /**
     * @param mixed $path
     *
     * @return void
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
}
