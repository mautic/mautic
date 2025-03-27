<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache\Adapter;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RedisTagAwareAdapter extends TagAwareAdapter
{
    use RedisAdapterTrait;

    /**
     * @param mixed[] $servers
     */
    public function __construct(
        #[Autowire(env: 'json:MAUTIC_CACHE_ADAPTER_REDIS')]
        array $servers,

        #[Autowire(env: 'string:MAUTIC_CACHE_PREFIX')]
        string $namespace,

        #[Autowire(env: 'int:MAUTIC_CACHE_LIFETIME')]
        int $lifetime,

        #[Autowire(env: 'bool:MAUTIC_REDIS_PRIMARY_ONLY')]
        bool $primaryOnly)
    {
        $client = $this->createClient($servers, $primaryOnly);

        parent::__construct(
            new RedisAdapter($client, $namespace, $lifetime),
            new RedisAdapter($client, $namespace.'.tags.', $lifetime)
        );
    }
}
