<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Factory;

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
     * IpLookupFactory constructor.
     *
     * @param array                $lookupServices
     * @param LoggerInterface|null $logger
     * @param null                 $cacheDir
     */
    public function __construct(array $lookupServices, LoggerInterface $logger = null, $cacheDir = null)
    {
        $this->lookupServices = $lookupServices;
        $this->logger         = $logger;
        $this->cacheDir       = $cacheDir;
    }

    /**
     * @param       $service
     * @param null  $auth
     * @param array $ipLookupConfig
     *
     * @return AbstractLookup
     */
    public function getService($service, $auth = null, array $ipLookupConfig = array())
    {
        static $services = array();

        if (!isset($services[$service]) || (null !== $auth || null !== $ipLookupConfig)) {
            if (!isset($this->lookupServices[$service])) {

                throw new \InvalidArgumentException($service.' not registered.');
            }

            $className = $this->lookupServices[$service]['class'];
            if (substr($className, 0, 1) !== '\\') {
                $className = '\\'.$className;
            }

            // @todo - remove in 2.0; BC support < 1.2.3
            if (is_subclass_of($className, 'Mautic\CoreBundle\IpLookup\AbstractIpLookup')) {
                $services[$service] = new $className(
                    null,
                    $auth,
                    $this->logger
                );
            } else {
                $services[$service] = new $className(
                    $auth,
                    $ipLookupConfig,
                    $this->cacheDir,
                    $this->logger
                );
            }
        }

        return $services[$service];
    }
}