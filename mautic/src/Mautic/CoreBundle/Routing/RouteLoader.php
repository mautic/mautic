<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Routing;

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
    protected $bundles   = array();
    protected $container;

    /**
     * @param Container $container
     * @param array     $bundles
     */
    public function __construct(Container $container, array $bundles)
    {
        $this->container = $container;
        //just need bundle names
        $this->bundles   = $bundles;
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

        $event      = new RouteEvent();
        $event->setLoader($this);
        $event->setCollection($collection);
        $this->container->get('event_dispatcher')->dispatch(CoreEvents::ROUTE_BUILD, $event);

        foreach($this->bundles as $bundle) {
            //Load bundle routing if routing.php exists
            $parts = explode("\\", $bundle);
            $path = __DIR__ . "/../../" . $parts[1] . "/Resources/config/routing.php";
            if (file_exists($path)) {
                $collection->addCollection($this->import($path));
            }
        }

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