<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

use Joomla\Http\Http;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Psr\Log\LoggerInterface;

class IpLookupFactory
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $lookupServices;

    /**
     * @var Http|null
     */
    protected $httpConnector;

    /**
     * IpLookupFactory constructor.
     *
     * @param array                $lookupServices
     * @param LoggerInterface|null $logger
     * @param Http|null            $httpConnector
     * @param null                 $cacheDir
     */
    public function __construct(array $lookupServices, LoggerInterface $logger = null, Http $httpConnector = null, $cacheDir = null)
    {
        $this->lookupServices = $lookupServices;
        $this->logger         = $logger;
        $this->cacheDir       = $cacheDir;
        $this->httpConnector  = $httpConnector;
    }

    /**
     * @param       $service
     * @param null  $auth
     * @param array $ipLookupConfig
     *
     * @return null|AbstractLookup
     */
    public function getService($service, $auth = null, array $ipLookupConfig = [])
    {
        static $services = [];

        if (empty($service)) {
            return null;
        }

        if (!isset($services[$service]) || (null !== $auth || null !== $ipLookupConfig)) {
            if (!isset($this->lookupServices[$service])) {
                throw new \InvalidArgumentException($service.' not registered.');
            }

            $className = $this->lookupServices[$service]['class'];
            if (substr($className, 0, 1) !== '\\') {
                $className = '\\'.$className;
            }

            $services[$service] = new $className(
                $auth,
                $ipLookupConfig,
                $this->cacheDir,
                $this->logger,
                $this->httpConnector
            );
        }

        return $services[$service];
    }
}
