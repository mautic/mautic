<?php

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * @deprecated This helper is deprecated in favor of CacheBundle
 */
class CacheStorageHelper
{
    public const ADAPTOR_DATABASE = 'db';

    public const ADAPTOR_FILESYSTEM = 'fs';

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var DoctrineDbalAdapter|FilesystemAdapter
     */
    protected $cacheAdaptor;

    protected string $cacheDir;

    /**
     * Semi BC support for pre 2.6.0.
     *
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @var array
     */
    protected $expirations = [];

    /**
     * @param mixed  $cacheDir
     * @param mixed  $namespace
     * @param int    $defaultExpiration
     * @param string $adaptor
     */
    public function __construct(
        protected $adaptor,
        protected $namespace = null,
        protected ?Connection $connection = null,
        $cacheDir = null,
        protected $defaultExpiration = 0
    ) {
        $this->cacheDir          = $cacheDir.'/data';

        // @deprecated BC support for pre 2.6.0 to be removed in 3.0
        if (!in_array($adaptor, [self::ADAPTOR_DATABASE, self::ADAPTOR_FILESYSTEM])) {
            if (file_exists($adaptor)) {
                $this->cacheDir = $adaptor.'/data';
            } else {
                throw new \InvalidArgumentException('cache directory either not set or does not exist; use the container\'s mautic.helper.cache_storage service.');
            }

            $this->adaptor = self::ADAPTOR_FILESYSTEM;
        }

        $this->setCacheAdaptor();
    }

    public function getAdaptorClassName(): string
    {
        return $this->cacheAdaptor::class;
    }

    /**
     * @return bool
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function set($name, $data, $expiration = null)
    {
        $cacheItem = $this->cacheAdaptor->getItem($name);

        if (null !== $expiration) {
            $cacheItem->expiresAfter((int) $expiration);
        } elseif ($data === $cacheItem->get()) {
            // Exact same data so don't update the cache unless expiration is set

            return false;
        }

        $cacheItem->set($data);

        return $this->cacheAdaptor->save($cacheItem);
    }

    /**
     * @param int $maxAge @deprecated 2.6.0 to be removed in 3.0; set expiration when using set()
     *
     * @return bool|mixed
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($name, $maxAge = null)
    {
        if (0 === $maxAge) {
            return false;
        } elseif (null !== $maxAge) {
        }

        $cacheItem = $this->cacheAdaptor->getItem($name);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        return false;
    }

    public function delete($name): void
    {
        $this->cacheAdaptor->deleteItem($name);
    }

    /**
     * @return bool
     */
    public function has($name)
    {
        return $this->cacheAdaptor->hasItem($name);
    }

    /**
     * Wipes out the cache directory.
     */
    public function clear(): void
    {
        $this->cacheAdaptor->clear();
    }

    /**
     * @return CacheStorageHelper;
     */
    public function getCache($namespace = null, $defaultExpiration = 0)
    {
        if (!$namespace) {
            return $this;
        }

        if (null === $defaultExpiration) {
            $defaultExpiration = $this->defaultExpiration;
        }

        if (!isset($this->cache[$namespace])) {
            $this->cache[$namespace] = new self($this->adaptor, $namespace, $this->connection, $this->cacheDir, (int) $defaultExpiration);
        }

        return $this->cache[$namespace];
    }

    /**
     * Creates adapter.
     */
    protected function setCacheAdaptor()
    {
        switch ($this->adaptor) {
            case self::ADAPTOR_DATABASE:
                $namespace          = ($this->namespace) ? InputHelper::alphanum($this->namespace, false, '-', ['-', '+', '.']) : '';

                $this->cacheAdaptor = new DoctrineDbalAdapter(
                    $this->connection, $namespace, $this->defaultExpiration, ['db_table' => MAUTIC_TABLE_PREFIX.'cache_items']
                );
                break;
            case self::ADAPTOR_FILESYSTEM:
                $namespace          = ($this->namespace) ? InputHelper::alphanum($this->namespace, false, '_', ['_', '-', '+', '.']) : '';
                $this->cacheAdaptor = new FilesystemAdapter($namespace, $this->defaultExpiration, $this->cacheDir);
                break;

            default:
                throw new \InvalidArgumentException('Cache adaptor not supported.');
        }
    }

    /**
     * Kept since it was public prior to deprecation.
     *
     * @deprecated 2.6.0 to be removed in 3.0
     */
    public function touchDir(): void
    {
    }
}
