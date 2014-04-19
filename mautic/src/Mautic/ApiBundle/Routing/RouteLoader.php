<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class RouteLoader
 *
 * @package Mautic\ApiBundle\Routing
 */

class RouteLoader extends Loader
{
    private   $loaded    = false;
    protected $bundles   = array();

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
            throw new \RuntimeException('Do not add the "mautic.api" loader twice');
        }

        $collection = new RouteCollection();
        if ($this->container->getParameter('mautic.api_enabled')) {
            //load routing files
            $iterator = new \FilesystemIterator(__DIR__ . '/../Resources/config/routing/');
            $filter   = new \RegexIterator($iterator, "/.php$/");
            if (iterator_count($filter)) {
                foreach ($filter as $file) {
                    $collection->addCollection($this->import($file->getPathname()));
                }
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
        return 'mautic.api' === $type;
    }
}