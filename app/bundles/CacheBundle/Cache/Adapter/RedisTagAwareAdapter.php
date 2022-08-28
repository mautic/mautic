<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache\Adapter;

use Mautic\CacheBundle\Exceptions\InvalidArgumentException;
use Mautic\CoreBundle\Helper\PRedisConnectionHelper;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class RedisTagAwareAdapter extends TagAwareAdapter
{
    public function __construct(array $servers, string $namespace, int $lifetime)
    {
        if (!isset($servers['dsn'])) {
            throw new InvalidArgumentException('Invalid redis configuration. No server specified.');
        }

        $options = array_key_exists('options', $servers) ? $servers['options'] : [];

        $client = new \Predis\Client(PRedisConnectionHelper::getRedisEndpoints($servers['dsn']), $options);

        parent::__construct(
            new RedisAdapter($client, $namespace, $lifetime),
            new RedisAdapter($client, $namespace.'.tags.', $lifetime)
        );
    }
}
