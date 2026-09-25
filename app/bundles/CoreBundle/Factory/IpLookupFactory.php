<?php

namespace Mautic\CoreBundle\Factory;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Psr\Log\LoggerInterface;

final class IpLookupFactory
{
    public function __construct(
        private array $lookupServices,
        private readonly LoggerInterface $logger,
        private readonly Client $client,
        private readonly CoreParametersHelper $coreParametersHelper,
        private readonly ?string $cacheDir = null,
    ) {
    }

    /**
     * @return AbstractLookup|null
     */
    public function getService($service, $auth = null, array $ipLookupConfig = [])
    {
        static $services = [];

        if (empty($service)) {
            return null;
        }

        if (!isset($services[$service]) || null !== $auth) {
            if (!isset($this->lookupServices[$service])) {
                throw new \InvalidArgumentException($service.' not registered.');
            }

            $className = $this->lookupServices[$service]['class'];
            if (!str_starts_with($className, '\\')) {
                $className = '\\'.$className;
            }

            $services[$service] = new $className(
                $auth,
                $ipLookupConfig,
                $this->cacheDir,
                $this->logger,
                $this->client,
                $this->coreParametersHelper
            );
        }

        return $services[$service];
    }
}
