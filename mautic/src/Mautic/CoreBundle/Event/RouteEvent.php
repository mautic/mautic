<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Routing\RouteLoader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class RouteEvent extends Event
{
    /**
     * @var
     */
    protected $loader;

    /**
     * @var
     */
    protected $collection;

    /**
     * @param RouteLoader $loader
     */
    public function setLoader(RouteLoader &$loader)
    {
        $this->loader =& $loader;
    }

    /**
     * @param RouteCollection $collection
     */
    public function setCollection(RouteCollection &$collection)
    {
        $this->collection =& $collection;
    }

    /**
     * @param $path
     */
    public function addRoutes($path)
    {
        $this->collection->addCollection($this->importer->import($path));
    }
}
