<?php

declare(strict_types=1);

return [
    'routes'   => [
        'main'   => [],
        'public' => [],
        'api'    => [],
    ],
    'menu'     => [],

    'parameters' => [
        'cache_adapter'           => Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter::class,
        'cache_adapter_tag_aware' => Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter::class,
        'cache_prefix'            => '',
        'cache_lifetime'          => 86400,
        'cache_adapter_memcached' => [
            'servers' => ['memcached://localhost'],
            'options' => [
                'compression'          => true,
                'libketama_compatible' => true,
                'serializer'           => 'igbinary',
            ],
        ],
        'cache_adapter_redis'     => [
            'dsn'     => 'redis://localhost',
            'options' => [
                'lazy'           => false,
                'persistent'     => 0,
                'persistent_id'  => null,
                'timeout'        => 30,
                'read_timeout'   => 0,
                'retry_interval' => 0,
            ],
        ],
    ],
];
