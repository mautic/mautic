<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Class CacheStorageHelper.
 */
class CacheStorageHelper
{
    const ADAPTOR_DATABASE = 'db';

    const ADAPTOR_FILESYSTEM = 'fs';

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var PdoAdapter|FilesystemAdapter
     */
    protected $cacheAdaptor;

    /**
     * @var string
     */
    protected $adaptor;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var int
     */
    protected $defaultExpiration;

    /**
     * Semi BC support for pre 2.6.0.
     *
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @var array
     */
    protected $expirations = [];

    /**
     * CacheStorageHelper constructor.
     *
     * @param                 $adaptor
     * @param null            $namespace
     * @param Connection|null $connection
     * @param null            $cacheDir
     * @param int             $defaultExpiration
     */
    public function __construct($adaptor, $namespace = null, Connection $connection = null, $cacheDir = null, $defaultExpiration = 0)
    {
        $this->cacheDir          = $cacheDir.'/data';
        $this->adaptor           = $adaptor;
        $this->namespace         = $namespace;
        $this->connection        = $connection;
        $this->defaultExpiration = $defaultExpiration;

        // @deprecated BC support for pre 2.6.0 to be removed in 3.0
        if (!in_array($adaptor, [self::ADAPTOR_DATABASE, self::ADAPTOR_FILESYSTEM])) {
            if (file_exists($adaptor)) {
                $this->cacheDir = $adaptor.'/data';
            } else {
                throw new \InvalidArgumentException(
                    'cache directory either not set or does not exist; use the container\'s mautic.helper.cache_storage service.'
                );
            }

            $this->adaptor = self::ADAPTOR_FILESYSTEM;
        }

        $this->setCacheAdaptor();
    }

    /**
     * @param $name
     * @param $data
     *
     * @return bool
     */
    public function set($name, $data, $expiration = null)
    {
        $cacheItem = $this->cacheAdaptor->getItem($name);

        if (null !== $expiration) {
            $cacheItem->expiresAfter((int) $expiration);
        } elseif (isset($this->expirations[$name])) {
            // @deprecated BC support to be removed in 3.0
            $cacheItem->expiresAfter($this->expirations[$name]);
        } elseif ($data === $cacheItem->get()) {
            // Exact same data so don't update the cache unless expiration is set

            return false;
        }

        $cacheItem->set($data);

        $this->cacheAdaptor->save($cacheItem);
    }

    /**
     * @param     $name
     * @param int $maxAge @deprecated 2.6.0 to be removed in 3.0; set expiration when using set()
     *
     * @return bool|mixed
     */
    public function get($name, $maxAge = null)
    {
        if (0 === $maxAge) {
            return false;
        } elseif (null !== $maxAge) {
            $this->expirations[$name] = (int) $maxAge;
        }

        $cacheItem = $this->cacheAdaptor->getItem($name);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        return false;
    }

    /**
     * @param $name
     */
    public function delete($name)
    {
        $this->cacheAdaptor->deleteItem($name);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return $this->cacheAdaptor->hasItem($name);
    }

    /**
     * Wipes out the cache directory.
     */
    public function clear()
    {
        $this->cacheAdaptor->clear();
    }

    /**
     * @param null $namespace
     * @param null $defaultExpiration
     *
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
     * @param $namespace
     * @param $defaultExpiration
     */
    protected function setCacheAdaptor()
    {
        switch ($this->adaptor) {
            case self::ADAPTOR_DATABASE:
                $namespace          = ($this->namespace) ? InputHelper::alphanum($this->namespace, false, '-', ['-', '+', '.']) : '';
                $this->cacheAdaptor = new PdoAdapter(
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
    public function touchDir()
    {
    }
}
