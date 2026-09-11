<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;

final class ResultCacheHelper
{
    /**
     * @return bool Returns true if cache was available and enabled on the $query
     */
    public static function enableOrmQueryCache(Query $query, ResultCacheOptions $resultCacheOptions): bool
    {
        $cache = self::getCache($query->getEntityManager()->getConfiguration());

        if (!$cache) {
            return false;
        }

        $query->setResultCacheProfile(self::createCacheProfile($resultCacheOptions, $cache));

        return true;
    }

    /**
     * Executes the query using cache (if available) and returns its result.
     */
    public static function executeCachedDbalQuery(Connection $connection, QueryBuilder $queryBuilder, ResultCacheOptions $resultCacheOptions): Result
    {
        $cache = self::getCache($connection->getConfiguration());

        if (!$cache) {
            return $queryBuilder->executeQuery();
        }

        return $connection->executeCacheQuery(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes(),
            self::createCacheProfile($resultCacheOptions, $cache)
        );
    }

    public static function getCache(Configuration $configuration): ?CacheItemPoolInterface
    {
        return $configuration->getResultCache();
    }

    /**
     * Returns a view of the pool scoped to $namespace: keys are prefixed with it, and
     * clear() drops only that namespace. This is what doctrine/cache's
     * setNamespace()/deleteAll() pair used to provide.
     */
    public static function getNamespacedCache(CacheItemPoolInterface $cache, string $namespace): AdapterInterface
    {
        return new ProxyAdapter($cache, $namespace);
    }

    private static function createCacheProfile(ResultCacheOptions $resultCacheOptions, CacheItemPoolInterface $cache): QueryCacheProfile
    {
        return new QueryCacheProfile(
            (int) $resultCacheOptions->getTtl(),
            $resultCacheOptions->getId(),
            self::getNamespacedCache($cache, $resultCacheOptions->getNamespace())
        );
    }
}
