<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query;

class ResultCacheHelper
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

    public static function getCache(Configuration $configuration): ?CacheProvider
    {
        $cache = $configuration->getResultCache();

        if (!$cache) {
            return null;
        }

        $cache = DoctrineProvider::wrap($cache);

        if (!$cache instanceof CacheProvider) {
            return null;
        }

        return $cache;
    }

    private static function createCacheProfile(ResultCacheOptions $resultCacheOptions, CacheProvider $cache): QueryCacheProfile
    {
        $cache = clone $cache;
        $cache->setNamespace($resultCacheOptions->getNamespace());

        return new QueryCacheProfile((int) $resultCacheOptions->getTtl(), $resultCacheOptions->getId(), $cache);
    }
}
