<?php

declare(strict_types=1);

namespace Mautic\CacheBundle\Cache\Adapter;

use Symfony\Component\Cache\Adapter\RedisAdapter as SymfonyRedisAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RedisAdapter extends SymfonyRedisAdapter
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
        parent::__construct($this->createClient($servers, $primaryOnly), $namespace, $lifetime);
    }
}
