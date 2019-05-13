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
     * @var
     */
    private $logger;

    /**
     * CacheClearSubscriber constructor.
     */
    public function __construct(AdapterInterface $cacheProvider, LoggerInterface $logger)
    {
        $this->cacheProvider = $cacheProvider;
        $this->logger        = $logger;
    }

    /**
     * @param string $cacheDir
     *
     * @throws \Exception
     */
    public function clear($cacheDir)
    {
        try {
            $reflect = new \ReflectionClass($this->cacheProvider->getCacheAdapter());
            $adapter = $reflect->getShortName();
        } catch (\ReflectionException $e) {
            $adapter = 'unknown';
        }

        try {
            if (!$this->cacheProvider->clear()) {
                $this->logger->emergency('Failed to clear Mautic cache.', ['adapter' => $adapter]);
                throw new \Exception('Failed to clear '.$adapter);
            }
        } catch (\PDOException $e) {
        }
    }
}
