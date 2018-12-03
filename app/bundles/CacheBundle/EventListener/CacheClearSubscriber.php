<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\EventListener;

use Mautic\CacheBundle\Cache\CacheProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Class CampaignSubscriber.
 */
class CacheClearSubscriber implements CacheClearerInterface
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(AdapterInterface $cacheProvider, LoggerInterface $logger)
    {
        $this->cacheProvider = $cacheProvider;
        $this->logger        = $logger;
    }

    /**
     * @param string $cacheDir
     *
     * @throws \ReflectionException
     */
    public function clear($cacheDir)
    {
        $reflect = new \ReflectionClass($this->cacheProvider->getCacheAdapter());
        $adapter = $reflect->getShortName();

        if (!$this->cacheProvider->clear()) {
            $this->logger->emergency('Failed to clear the Mautic cache.', ['adapter' => $adapter]);
            throw new \Exception('Failed to clear '.$adapter);
        }

        $this->logger->info('Cleared Mautic cache.', ['adapter'=>$adapter]);
    }
}
