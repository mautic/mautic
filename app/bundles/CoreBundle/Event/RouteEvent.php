<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Loader\RouteLoader;
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

    public function __construct(RouteLoader &$loader, RouteCollection &$collection)
    {
        $this->loader     =& $loader;
        $this->collection =& $collection;
    }

    /**
     * @param $path
     */
    public function addRoutes($path)
    {
        $this->collection->addCollection($this->loader->import($path));
    }
}