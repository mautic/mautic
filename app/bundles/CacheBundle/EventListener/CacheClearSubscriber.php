<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheClearSubscriber implements CacheClearerInterface
{
    public function __construct(private AdapterInterface $cacheProvider, private LoggerInterface $logger)
    {
    }

    /**
     * @param string $cacheDir
     *
     * @throws \Exception
     */
    public function clear($cacheDir): void
    {
        try {
            $reflect = new \ReflectionClass($this->cacheProvider->getCacheAdapter());
            $adapter = $reflect->getShortName();
        } catch (\ReflectionException) {
            $adapter = 'unknown';
        }

        try {
            if (!$this->cacheProvider->clear()) {
                $this->logger->emergency('Failed to clear Mautic cache.', ['adapter' => $adapter]);
                throw new \Exception('Failed to clear '.$adapter);
            }
        } catch (\PDOException) {
        }
    }
}
