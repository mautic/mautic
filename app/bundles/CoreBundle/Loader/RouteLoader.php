<?php

namespace Mautic\CoreBundle\Loader;

use Mautic\CoreBundle\Event\RouteEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;

final class RouteLoader extends Loader
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly CoreParametersHelper $coreParameters,
    ) {
    }

    /**
     * Load each bundles routing.php file.
     *
     * @return RouteCollection
     *
     * @throws \RuntimeException
     */
    public function load(mixed $resource, ?string $type = null): mixed
    {
        // Public
        $event = new RouteEvent($this, 'public');
        $this->dispatcher->dispatch($event);
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
        $this->dispatcher->dispatch($event);
        $secureCollection = $event->getCollection();

        // OneupUploader (added behind our secure /s)
        $secureCollection->addCollection($this->import('.', 'uploader'));

        // Elfinder file manager
        $collection->addCollection($this->import('@FMElfinderBundle/Resources/config/routing.yaml'));

        // API
        $event = new RouteEvent($this, 'api');
        $this->dispatcher->dispatch($event);
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

        // Native #[Route] attributes declared directly on bundle controllers.
        // Paths already carry their full prefix (e.g. /s, /api), so they are added
        // to the root collection; forceSSL is applied here like every other group.
        //
        // Skipped during installation: scanning every controller for attributes
        // autoloads all of them, and the installer runs under a tight memory limit.
        // The installer's own routes live on the config loader, so /installer still
        // resolves without this scan.
        if (!defined('MAUTIC_INSTALLER')) {
            $attributeCollection = new RouteCollection();
            foreach (glob(dirname(__DIR__, 2).'/*/Controller', GLOB_ONLYDIR) as $controllerDir) {
                $attributeCollection->addCollection($this->import($controllerDir, 'attribute'));
            }
            if ($forceSSL) {
                $attributeCollection->setSchemes('https');
            }
            $collection->addCollection($attributeCollection);
        }

        // Catch all
        $event = new RouteEvent($this, 'catchall');
        $this->dispatcher->dispatch($event);
        $lastCollection = $event->getCollection();

        if ($forceSSL) {
            $lastCollection->setSchemes('https');
        }

        $collection->addCollection($lastCollection);

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'mautic' === $type;
    }
}
