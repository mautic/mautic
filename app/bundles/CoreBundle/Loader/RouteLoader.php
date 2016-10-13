<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteLoader.
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
        $this->factory = $factory;
    }

    /**
     * Load each bundles routing.php file.
     *
     * @param mixed $resource
     * @param null  $type
     *
     * @return RouteCollection
     *
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

        // Force all links to be SSL if the site_url parameter is SSL
        $siteUrl  = $this->factory->getParameter('site_url');
        $forceSSL = false;
        if (!empty($siteUrl)) {
            $parts    = parse_url($siteUrl);
            $forceSSL = (!empty($parts['scheme']) && $parts['scheme'] == 'https');
        }

        if ($forceSSL) {
            $collection->setSchemes('https');
        }

        // Secured area - Default
        $event = new RouteEvent($this);
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $secureCollection = $event->getCollection();

        // OneupUploader (added behind our secure /s)
        $secureCollection->addCollection($this->import('.', 'uploader'));

        //API
        $event = new RouteEvent($this, 'api');
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $apiCollection = $event->getCollection();
        $apiCollection->addPrefix('/api');

        if ($forceSSL) {
            $apiCollection->setSchemes('https');
        }

        $collection->addCollection($apiCollection);

        $secureCollection->addPrefix('/s');
        if ($forceSSL) {
            $secureCollection->setSchemes('https');
        }
        $collection->addCollection($secureCollection);

        // Catch all
        $event = new RouteEvent($this, 'catchall');
        $dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $lastCollection = $event->getCollection();

        if ($forceSSL) {
            $lastCollection->setSchemes('https');
        }

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
