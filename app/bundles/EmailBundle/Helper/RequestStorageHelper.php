<?php

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helper class for storing request payload to a cache location and retrieving it back as a Request.
 */
class RequestStorageHelper
{
    /**
     * Separator between the transport class name and random hash.
     */
    const KEY_SEPARATOR = ':webhook_request:';

    /**
     * @var CacheStorageHelper
     */
    private $cacheStorage;

    public function __construct(CacheStorageHelper $cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Stores the request content into cache and returns the unique key under which it's stored.
     *
     * @param string $transportName
     *
     * @return string
     */
    public function storeRequest($transportName, Request $request)
    {
        $key = $this->getUniqueCacheHash($transportName);

        $this->cacheStorage->set($key, $request->request->all());

        return $key;
    }

    /**
     * Creates new Request with the original payload.
     *
     * @param string $key
     *
     * @return Request
     *
     * @throws \UnexpectedValueException
     */
    public function getRequest($key)
    {
        $key           = $this->removeCachePrefix($key);
        $cachedRequest = $this->cacheStorage->get($key);

        if (false === $cachedRequest) {
            throw new \UnexpectedValueException("Request with key '{$key}' was not found in the cache store '{$this->cacheStorage->getAdaptorClassName()}'.");
        }

        return new Request([], $cachedRequest);
    }

    /**
     * @param string $key
     */
    public function deleteCachedRequest($key)
    {
        $key = $this->removeCachePrefix($key);

        $this->cacheStorage->delete($key);
    }

    /**
     * Reads the transport class name path from the key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getTransportNameFromKey($key)
    {
        $key = $this->removeCachePrefix($key);

        // Take the part before the key separator as the serialized transpot name.
        list($serializedTransportName) = explode(self::KEY_SEPARATOR, $key);

        // Unserialize transport name to the standard full class name.
        $transportName = str_replace('|', '\\', $serializedTransportName);

        return $transportName;
    }

    /**
     * Remove the default cache key prefix if set.
     *
     * @param string $key
     *
     * @return string
     */
    private function removeCachePrefix($key)
    {
        if (0 === strpos($key, 'mautic:')) {
            $key = ltrim($key, 'mautic:');
        }

        return $key;
    }

    /**
     * Generates unique hash in format $transportName:webhook_request:unique.hash.
     *
     * @param string $transportName
     *
     * @return string
     *
     * @throws \LengthException
     */
    private function getUniqueCacheHash($transportName)
    {
        $key       = uniqid($transportName.self::KEY_SEPARATOR, true);
        $keyLength = strlen($key);

        if ($keyLength > 191) {
            throw new \LengthException(sprintf('Key %s must be shorter than 191 characters. It has %d characters', $key, $keyLength));
        }

        return $key;
    }
}
