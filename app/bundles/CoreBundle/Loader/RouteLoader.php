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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteLoader.
 */
class RouteLoader extends Loader
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var CoreParametersHelper
     */
    private $coreParameters;

    /**
     * RouteLoader constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher, CoreParametersHelper $parametersHelper)
    {
        $this->dispatcher     = $dispatcher;
        $this->coreParameters = $parametersHelper;
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
        // Public
        $event = new RouteEvent($this, 'public');
        $this->dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $collection = $event->getCollection();

        // Force all links to be SSL if the site_url parameter is SSL
        $siteUrl  = $this->coreParameters->get('site_url');
        $forceSSL = false;
        if (!empty($siteUrl)) {
            $parts    = parse_url($siteUrl);
            $forceSSL = (!empty($parts['scheme']) && 'https' == $parts['scheme']);
        }

        if ($forceSSL) {
            $collection->setSchemes('https');
        }

        // Secured area - Default
        $event = new RouteEvent($this);
        $this->dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $secureCollection = $event->getCollection();

        // OneupUploader (added behind our secure /s)
        $secureCollection->addCollection($this->import('.', 'uploader'));

        // Elfinder file manager
        $collection->addCollection($this->import('@FMElfinderBundle/Resources/config/routing.yaml'));

        //API
        if ($this->coreParameters->get('api_enabled')) {
            $event = new RouteEvent($this, 'api');
            $this->dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
            $apiCollection = $event->getCollection();
            $apiCollection->addPrefix('/api');

            if ($forceSSL) {
                $apiCollection->setSchemes('https');
            }

            $collection->addCollection($apiCollection);
        }

        $secureCollection->addPrefix('/s');
        if ($forceSSL) {
            $secureCollection->setSchemes('https');
        }
        $collection->addCollection($secureCollection);

        // Catch all
        $event = new RouteEvent($this, 'catchall');
        $this->dispatcher->dispatch(CoreEvents::BUILD_ROUTE, $event);
        $lastCollection = $event->getCollection();

        if ($forceSSL) {
            $lastCollection->setSchemes('https');
        }

        $collection->addCollection($lastCollection);

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
