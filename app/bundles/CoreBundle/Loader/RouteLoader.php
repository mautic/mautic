<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteLoader
 */
class RouteLoader extends Loader
{

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory  = $factory;
    }

    /**
     * Load each bundles routing.php file
     *
     * @param mixed $resource
     * @param null  $type
     *
     * @return RouteCollection
     * @throws \RuntimeException
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "mautic" loader twice');
        }

        $dispatcher = $this->factory->getDispatcher();

        // Public
        $event = new RouteEvent($this, 'public');
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $collection = $event->getCollection();

        // Secured area - Default
        $event = new RouteEvent($this);
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $secureCollection = $event->getCollection();

        // OneupUploader (added behind our secure /s)
        $secureCollection->addCollection($this->import('.', 'uploader'));

        if ($this->factory->getParameter('api_enabled')) {
            //API
            $event = new RouteEvent($this, 'api');
            $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
            $apiCollection = $event->getCollection();
            $apiCollection->addPrefix('/api');
            $collection->addCollection($apiCollection);
        }

        $secureCollection->addPrefix('/s');
        $collection->addCollection($secureCollection);

        // Catch all
        $event = new RouteEvent($this, 'catchall');
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $lastCollection = $event->getCollection();
        $collection->addCollection($lastCollection);

        $this->loaded = true;

        return $collection;
    }

    /**
     * @param mixed $resource
     * @param null  $type
     *
     * @return bool
     */
    public function supports($resource, $type = null)
    {
        return 'mautic' === $type;
    }
}
