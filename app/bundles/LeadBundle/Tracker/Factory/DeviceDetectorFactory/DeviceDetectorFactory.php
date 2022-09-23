<?php

namespace Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory;

use DeviceDetector\Cache\PSR6Bridge;
use DeviceDetector\DeviceDetector;
use Mautic\CacheBundle\Cache\CacheProvider;

/**
 * Class DeviceDetectorFactory.
 */
final class DeviceDetectorFactory implements DeviceDetectorFactoryInterface
{
    private CacheProvider $cacheProvider;

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param string $userAgent
     *
     * @return DeviceDetector
     *
     * @throws \Exception
     */
    public function create($userAgent)
    {
        $detector = new DeviceDetector((string) $userAgent);
        $bridge   = new PSR6Bridge($this->cacheProvider->getCacheAdapter());
        $detector->setCache($bridge);

        return $detector;
    }
}
