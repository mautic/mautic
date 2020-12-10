<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes'   => [
        'main'   => [],
        'public' => [],
        'api'    => [],
    ],
    'menu'     => [],
    'services' => [
        'events'    => [
            'mautic.cache.clear_cache_subscriber' => [
                'class'     => \Mautic\CacheBundle\EventListener\CacheClearSubscriber::class,
                'tags'      => ['kernel.cache_clearer'],
                'arguments' => [
                    'mautic.cache.provider',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'forms'     => [],
        'helpers'   => [],
        'menus'     => [],
        'other'     => [
            'mautic.cache.provider'           => [
                'class'     => \Mautic\CacheBundle\Cache\CacheProvider::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'service_container',
                ],
            ],
            'mautic.cache.adapter.filesystem' => [
                'class'     => \Mautic\CacheBundle\Cache\Adapter\FilesystemTagAwareAdapter::class,
                'arguments' => [
                    '%mautic.cache_prefix%',
                    '%mautic.cache_lifetime%',
                ],
                'tag'       => 'mautic.cache.adapter',
            ],
            'mautic.cache.adapter.memcached'  => [
                'class'     => \Mautic\CacheBundle\Cache\Adapter\MemcachedTagAwareAdapter::class,
                'arguments' => [
                    '%mautic.cache_adapter_memcached%',
                    '%mautic.cache_prefix%',
                    '%mautic.cache_lifetime%',
                ],
                'tag'       => 'mautic.cache.adapter',
            ],
            'mautic.cache.adapter.redis'      => [
                'class'     => \Mautic\CacheBundle\Cache\Adapter\RedisTagAwareAdapter::class,
                'arguments' => [
                    '%mautic.cache_adapter_redis%',
                    '%mautic.cache_prefix%',
                    '%mautic.cache_lifetime%',
                ],
                'tag'       => 'mautic.cache.adapter',
            ],
        ],
        'models'    => [],
        'validator' => [],
    ],

    'parameters' => [
        'cache_adapter'           => 'mautic.cache.adapter.filesystem',
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
