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

use GuzzleHttp\Client;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Psr\Log\LoggerInterface;

class IpLookupFactory
{
    protected ?LoggerInterface $logger;
    protected ?string $cacheDir;
    protected array $lookupServices;
    protected ?Client $client;

    public function __construct(array $lookupServices, ?LoggerInterface $logger = null, ?Client $client = null, ?string $cacheDir = null)
    {
        $this->lookupServices = $lookupServices;
        $this->logger         = $logger;
        $this->cacheDir       = $cacheDir;
        $this->client         = $client;
    }

    /**
     * @param      $service
     * @param null $auth
     *
     * @return AbstractLookup|null
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
            if ('\\' !== substr($className, 0, 1)) {
                $className = '\\'.$className;
            }

            $services[$service] = new $className(
                $auth,
                $ipLookupConfig,
                $this->cacheDir,
                $this->logger,
                $this->client
            );
        }

        return $services[$service];
    }
}
