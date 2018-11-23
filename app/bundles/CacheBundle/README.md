# mautic-cache-plugin

Enables PSR-6 and PSR-16 caching

## Installation

Install this plugin as submodule into plugins directory.

## Configuration

Plugin comes preconfigured to utilize filesystem caching.

These are the default settings:
```
'cache_adapter'        => 'mautic.cache.adapter.filesystem',
'cache_prefix'         => 'app',
'cache_lifetime'       => 86400
```


They can be overridden in **local.php** like this:

```
'cache_adapter' => 'mautic.cache.adapter.redis',
'cache_prefix' => 'app_cache',
'cache_lifetime' => 86400,
```


## Delivered adapters
 * mautic.cache.adapter.filesystem
 * mautic.cache.adapter.memcached
```
 'memcached' => [
         'servers' => ['memcached://localhost'],
         'options' => [
             'compression'          => true,
             'libketama_compatible' => true,
             'serializer'           => 'igbinary',
         ],
     ],
```
 * mautic.cache.adapter.redis
 Redis configuration in **local.php**:
 ```
 'redis' => [
         'dsn' => 'redis://localhost',
         'options' => [
             'lazy' => false,
             'persistent' => 0,
             'persistent_id' => null,
             'timeout' => 30,
             'read_timeout' => 0,
             'retry_interval' => 0,
         ]
     ],
```

In order to use another adapter just set it up as a service

## Clearing the cache

The cache is cleared when **cache:clear** command is run. The cache can be cleared by running

```bash
app/console  mautic:cache:clear
```

## Features auto pruning on adapter initialization

## Usage

### PSR-6

```php
    /** @var CacheProvider $cache */
    $cache = $this->get('mautic.cache.provider');

    /** @var CacheItemInterface $item */
    $item = $cache->getItem('test_tagged_Item');
    $item->set('yesa!!!');
    $item->tag(['firstTag', 'secondTag']);
    $item->expiresAfter(20000);

    $cache->save($item);

    $item = $cache->getItem('test_nottagged_Item2');
    $item->tag(['firstTag']);
    $cache->save($item);

    $item = $cache->getItem('test_nottagged_Item3');
    $item->tag(['secondTag']);
    $cache->save($item);

    $cache->commit();

    var_dump($cache->getItem('test_nottagged_Item2')->isHit());
    var_dump($cache->getItem('test_nottagged_Item3')->isHit());

    $cache->invalidateTags(['firstTag']);

    var_dump($cache->getItem('test_nottagged_Item2')->isHit());
    var_dump($cache->getItem('test_nottagged_Item3')->isHit());

    $cache->commit();
```

### PSR-16

```php
        /** @var CacheProvider $cache */
        $cache = $this->get('mautic.cache.provider');


        $simpleCache = $cache->getSimpleCache();
        $test = $simpleCache->get('test_value');

        var_dump($test);
```
