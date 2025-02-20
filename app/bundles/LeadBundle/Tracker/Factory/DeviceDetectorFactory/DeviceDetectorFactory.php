<?php

namespace Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory;

use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use Mautic\CacheBundle\Cache\CacheProvider;

final class DeviceDetectorFactory implements DeviceDetectorFactoryInterface
{
    public function __construct(
        private CacheProvider $cacheProvider,
    ) {
    }

    /**
     * @param string $userAgent
     *
     * @throws \Exception
     */
    public function create($userAgent): DeviceDetector
    {
        $detector = new DeviceDetector((string) $userAgent);
        $bridge   = new PSR6Bridge($this->cacheProvider->getCacheAdapter());
        $detector->setCache($bridge);

        return $detector;
    }
}
