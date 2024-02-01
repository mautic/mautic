<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Cache\ResultCacheHelper;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Integration>
 */
class IntegrationRepository extends CommonRepository
{
    /**
     * @return mixed[]
     */
    public function getIntegrations(): array
    {
        $query = $this->createQueryBuilder('i')
            ->join('i.plugin', 'p')
            ->getQuery();
        $this->enableCache($query);

        $services = $query->getResult();

        $results = [];
        foreach ($services as $s) {
            $results[$s->getName()] = $s;
        }

        return $results;
    }

    /**
     * Get core (no plugin) integrations.
     *
     * @return mixed[]
     */
    public function getCoreIntegrations(): array
    {
        $query = $this->createQueryBuilder('i')
            ->getQuery();
        $this->enableCache($query);

        $services = $query->getResult();

        $results = [];
        foreach ($services as $s) {
            $results[$s->getName()] = $s;
        }

        return $results;
    }

    public function findOneByName(string $name): ?Integration
    {
        $query = $this->createQueryBuilder('i')
            ->where('i.name = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery();
        $this->enableCache($query);

        return $query->getOneOrNullResult();
    }

    private function enableCache(Query $query): void
    {
        ResultCacheHelper::enableOrmQueryCache($query, new ResultCacheOptions(Integration::CACHE_NAMESPACE));
    }
}
