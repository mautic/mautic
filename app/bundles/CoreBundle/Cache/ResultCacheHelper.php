<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\ForwardCompatibility\DriverResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;

class ResultCacheHelper
{
    /**
     * @return bool Returns true if cache was available and enabled on the $query
     */
    public static function enableOrmQueryCache(Query $query, ResultCacheOptions $resultCacheOptions): bool
    {
        $cache = $query->getEntityManager()
            ->getConfiguration()
            ->getResultCacheImpl();

        if (!$cache instanceof CacheProvider) {
            return false;
        }

        $query->setResultCacheProfile(self::createCacheProfile($resultCacheOptions, $cache));

        return true;
    }

    /**
     * Executes the query using cache (if available) and returns its result.
     *
     * @return DriverResultStatement<mixed>|int|string
     */
    public static function executeCachedDbalQuery(QueryBuilder $queryBuilder, ResultCacheOptions $resultCacheOptions)
    {
        $connection = $queryBuilder->getConnection();
        $cache      = $connection
            ->getConfiguration()
            ->getResultCacheImpl();

        if (!$cache instanceof CacheProvider) {
            return $queryBuilder->execute();
        }

        return $connection->executeCacheQuery(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes(),
            self::createCacheProfile($resultCacheOptions, $cache)
        );
    }

    private static function createCacheProfile(ResultCacheOptions $resultCacheOptions, CacheProvider $cache): QueryCacheProfile
    {
        $cache = clone $cache;
        $cache->setNamespace($resultCacheOptions->getNamespace());

        return new QueryCacheProfile((int) $resultCacheOptions->getTtl(), $resultCacheOptions->getId(), $cache);
    }
}
