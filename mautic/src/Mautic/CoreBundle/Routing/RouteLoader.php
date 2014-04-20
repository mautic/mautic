<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Routing;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\RouteEvent;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class RouteLoader
 *
 * @package Mautic\CoreBundle\Routing
 */

class RouteLoader extends Loader
{
    private   $loaded    = false;
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Load each bundles routing.php file
     *
     * @param mixed $resource
     * @param null  $type
     * @return RouteCollection
     * @throws \RuntimeException
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "mautic" loader twice');
        }

        $collection = new RouteCollection();

        $event      = new RouteEvent($this, $collection);
        $this->container->get('event_dispatcher')->dispatch(CoreEvents::BUILD_ROUTE, $event);

        $this->loaded = true;

        return $collection;
    }

    /**
     * @param mixed $resource
     * @param null  $type
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return 'mautic' === $type;
    }
}