<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    /**
     * @param CacheStorageHelper $cacheStorage
     */
    public function __construct(CacheStorageHelper $cacheStorage)
    {
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * Stores the request content into cache and returns the unique key under which it's stored.
     *
     * @param string  $transportName
     * @param Request $request
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
     */
    public function getRequest($key)
    {
        return new Request([], $this->cacheStorage->get($key));
    }

    /**
     * @param string $key
     */
    public function deleteCachedRequest($key)
    {
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
        list($transportName) = explode(self::KEY_SEPARATOR, $key);

        return $transportName;
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

        if ($keyLength > 255) {
            throw new \LengthException(sprintf('Key %s must be shorter than 256 characters. It has %d characters', $key, $keyLength));
        }

        return $key;
    }
}
