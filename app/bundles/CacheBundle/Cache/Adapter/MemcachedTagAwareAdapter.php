<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache\Adapter;

use Mautic\CacheBundle\Exceptions\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class MemcachedTagAwareAdapter extends TagAwareAdapter
{
    public function __construct(array $servers, string $namespace, int $lifetime)
    {
        if (!isset($servers['servers'])) {
            throw new InvalidArgumentException('Invalid memcached configuration. No servers specified.');
        }

        $options = array_key_exists('options', $servers) ? $servers['options'] : [];
        $client  = MemcachedAdapter::createConnection($servers['servers'], $options);

        parent::__construct(
            new MemcachedAdapter($client, $namespace, $lifetime),
            new MemcachedAdapter($client, $namespace, $lifetime)
        );
    }
}
