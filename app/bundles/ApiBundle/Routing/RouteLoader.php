<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Routing;

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
     * @var bool|mixed
     */
    private $bundles;

    /**
     * @var bool|mixed
     */
    private $addonBundles;

    /**
     * @var bool
     */
    private $apiEnabled;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->bundles      = $factory->getParameter('bundles');
        $this->addonBundles = $factory->getParameter('addon.bundles');
        $this->apiEnabled   = $factory->getParameter('api_enabled');
    }

    /**
     * {@inheritdoc}
     *
     * @return RouteCollection
     * @throws \RuntimeException
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "mautic.api" loader twice');
        }

        $collection = new RouteCollection();
        foreach ($this->bundles as $bundle) {
            $routing = $bundle['directory'] . "/Config/routing/api.php";
            if (file_exists($routing)) {
                $collection->addCollection($this->import($routing));
            }
        }

        foreach ($this->addonBundles as $bundle) {
            $routing = $bundle['directory'] . "/Config/routing/api.php";
            if (file_exists($routing)) {
                $collection->addCollection($this->import($routing));
            }
        }

        $this->loaded = true;

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'mautic.api' === $type;
    }
}
