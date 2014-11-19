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

/**
 * Class ApiDocsLoader
 */
class ApiDocsLoader extends Loader
{

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param string $environment
     */
    public function __construct($environment)
    {
        $this->environment = $environment;
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
            throw new \RuntimeException('Do not add the "mautic.api_docs" loader twice');
        }

        $collection = new RouteCollection();

        if ($this->environment == 'dev') {
            //Load API doc routing
            $apiDoc = $this->import("@NelmioApiDocBundle/Resources/config/routing.yml");
            $apiDoc->addPrefix('/docs/api');
            $collection->addCollection($apiDoc);
        }

        $this->loaded = true;

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'mautic.api_docs' === $type;
    }
}
