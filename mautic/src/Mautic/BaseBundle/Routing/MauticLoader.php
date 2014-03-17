<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\BaseBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class MauticLoader
 *
 * @package Mautic\BaseBundle\Routing
 */

class MauticLoader extends Loader
{
    private $loaded = false;
    protected $bundles = array();

    /**
     * @param $bundles
     */
    public function __construct($bundles)
    {
        $this->bundles = array_keys($bundles);
    }

    /**
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

        foreach($this->bundles as $bundle) {
            $bundleRoutes = $this->import("@{$bundle}/Resources/config/routing.php", 'php');
            $collection->addCollection($bundleRoutes);
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